<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Tests\Support\JourneyExecutionTestCase;

class SessionRecoveryTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['session.lifetime'=>720]);
        $this->withoutMiddleware([HandleInertiaRequests::class, \Inertia\Middleware::class]);
    }

    public function test_probe_never_redirects_guests_or_returns_a_csrf_secret(): void
    {
        $this->getJson('/session/status')->assertUnauthorized()->assertJsonPath('authenticated', false)->assertHeader('Cache-Control','no-store, private');
        $user=User::factory()->make(['id'=>1,'role'=>UserRole::DRIVER,'email_verified_at'=>now(),'is_admin'=>false]);
        $response=$this->actingAs($user)->getJson('/session/status')->assertOk()->assertJsonPath('authenticated',true)->assertJsonPath('userId',1);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/',$response->json('csrfFingerprint'));
        $this->assertArrayNotHasKey('csrf_token',$response->json());
    }

    public function test_login_recovery_preserves_a_local_protected_destination(): void
    {
        $this->get('/session/login?return_to='.urlencode('/journeys/1/execution?return_to=%2Fdashboard-driver'))
            ->assertRedirect('/login?expired=1')->assertSessionHas('url.intended','/journeys/1/execution?return_to=%2Fdashboard-driver');
    }

    public function test_login_recovery_rejects_open_redirects_and_auth_api_loops(): void
    {
        foreach(['https://evil.example/','//evil.example','/%2Fevil.example','/\\evil.example',"/journey\r\nLocation:evil",'/login','/forgot-password','/session/status','/api/journeys/1/execution','/missing-route'] as $return) {
            $this->get('/session/login?return_to='.urlencode($return))->assertRedirect('/login?expired=1')->assertSessionHas('url.intended','/dashboard');
        }
    }
    public function test_database_session_survives_a_turn_but_expires_after_twelve_idle_hours(): void
    {
        $this->assertSame(720,(int) config('session.lifetime'));
        \Illuminate\Support\Facades\Schema::create('sessions',function($t){$t->string('id')->primary();$t->text('payload');$t->integer('last_activity');});
        $handler=new \Illuminate\Session\DatabaseSessionHandler(\Illuminate\Support\Facades\DB::connection(),'sessions',(int)config('session.lifetime'),app());
        \Illuminate\Support\Facades\DB::table('sessions')->insert(['id'=>'turn','payload'=>base64_encode('saved-session'),'last_activity'=>now()->subHours(11)->timestamp]);
        $this->assertSame('saved-session',$handler->read('turn'));
        \Illuminate\Support\Facades\DB::table('sessions')->where('id','turn')->update(['last_activity'=>now()->subHours(13)->timestamp]);
        $this->assertSame('',$handler->read('turn'));
    }

    public function test_reauthentication_returns_to_the_original_journey_instead_of_the_dashboard(): void
    {
        \Illuminate\Support\Facades\Schema::table('users',function($t){$t->boolean('can_login')->default(true);$t->string('password')->nullable();$t->string('remember_token')->nullable();$t->timestamp('email_verified_at')->nullable();});
        \Illuminate\Support\Facades\DB::table('users')->where('id',1)->update(['email'=>'driver-session@example.test','password'=>\Illuminate\Support\Facades\Hash::make('test-password'),'email_verified_at'=>now()]);
        $this->get('/session/login?return_to='.urlencode('/journeys/1/execution'))->assertRedirect();
        $this->post('/login',['email'=>'driver-session@example.test','password'=>'test-password'])->assertRedirect('/journeys/1/execution');
        $this->assertAuthenticatedAs(User::findOrFail(1));
    }

    public function test_expired_csrf_response_is_friendly_for_axios_and_inertia(): void
    {
        \Illuminate\Support\Facades\Route::post('/session-csrf-fixture',fn()=>throw new \Illuminate\Session\TokenMismatchException());
        $this->postJson('/session-csrf-fixture')->assertStatus(419)->assertHeader('X-Session-Expired','1')->assertJsonPath('message','La sessione è scaduta. Accedi nuovamente per continuare.');
        $this->post('/session-csrf-fixture',[],['X-Inertia'=>'true'])->assertStatus(419)->assertHeader('X-Session-Expired','1');
    }

    public function test_release_command_changes_only_session_lifetime_and_checks_effective_config(): void
    {
        $dir=sys_get_temp_dir().'/session-lifetime-'.bin2hex(random_bytes(6));mkdir($dir,0700);
        file_put_contents($dir.'/.env',"APP_NAME=Test\nSESSION_LIFETIME=120\nOTHER_SETTING=unchanged\n");chmod($dir.'/.env',0600);
        $previous=app()->environmentPath();app()->useEnvironmentPath($dir);
        try {
            $this->artisan('session:lifetime',['minutes'=>720])->assertSuccessful();
            $this->assertSame("APP_NAME=Test\nSESSION_LIFETIME=720\nOTHER_SETTING=unchanged\n",file_get_contents($dir.'/.env'));
            $this->assertSame(0600,fileperms($dir.'/.env') & 0777);
            config(['session.lifetime'=>120]);$this->artisan('session:lifetime',['minutes'=>720,'--check'=>true])->assertFailed();
            config(['session.lifetime'=>720]);$this->artisan('session:lifetime',['minutes'=>720,'--check'=>true])->assertSuccessful();
        } finally {app()->useEnvironmentPath($previous);unlink($dir.'/.env');rmdir($dir);}
    }

    public function test_a_stale_tab_cannot_write_as_a_different_session_user(): void
    {
        $writes=0;
        \Illuminate\Support\Facades\Route::middleware('web')->post('/session-owner-fixture',function()use(&$writes){$writes++;return response()->json(['written'=>true]);});
        $user=User::factory()->make(['id'=>1,'role'=>UserRole::DRIVER,'is_admin'=>false,'email_verified_at'=>now()]);
        $this->actingAs($user)->postJson('/session-owner-fixture',[],['X-Session-User'=>'2'])->assertUnauthorized()->assertHeader('X-Session-Expired','1');
        $this->assertSame(0,$writes);
        $this->postJson('/session-owner-fixture',[],['X-Session-User'=>'1'])->assertOk();
        $this->assertSame(1,$writes);
    }

}
