<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Journey;
use App\Models\Site;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use App\Services\JourneyExecution\JourneySiteNotes;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;
use Tests\Support\JourneyExecutionTestCase;

class JourneySiteNotesTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([HandleInertiaRequests::class, Middleware::class]);
        $this->login();
    }

    private function login(int $id = 1, UserRole $role = UserRole::DRIVER): void
    {
        $this->actingAs(User::factory()->make(['id' => $id, 'role' => $role, 'is_admin' => false, 'email_verified_at' => now()]));
    }

    private function saveNotes(?string $notes, ?string $version = null, int $site = 1)
    {
        return $this->putJson('/api/journeys/1/sites/'.$site.'/notes', ['notes' => $notes, 'notes_version' => $version ?? JourneySiteNotes::version(Site::find($site)?->notes)]);
    }

    public function test_assigned_driver_updates_only_live_notes_with_actor_and_diff_without_mutating_the_plan(): void
    {
        $this->start();
        $journey = Journey::find(1);
        $baseline = $journey->baseline->data;
        $execution = $journey->orderExecutions()->first()->data;
        $version = JourneySiteNotes::version(null);
        $this->saveNotes('Nuova sbarra: telefonare prima di entrare.', $version)->assertOk()
            ->assertJsonPath('journey.orders.0.site.notes', 'Nuova sbarra: telefonare prima di entrare.')
            ->assertJsonPath('journey.orders.1.site.notes', 'Nuova sbarra: telefonare prima di entrare.');
        $this->assertSame('Nuova sbarra: telefonare prima di entrare.', Site::find(1)->notes);
        $this->assertSame($baseline, $journey->baseline->fresh()->data);
        $this->assertSame($execution, $journey->orderExecutions()->first()->data);
        $this->assertSame(1, Journey::find(1)->execution_revision);
        $event = $journey->events()->latest('id')->first();
        $this->assertSame(1, $event->created_by_user_id);
        $this->assertSame('site_notes_updated', $event->payload['event']);
        $this->assertSame(['path' => 'sites.1.notes', 'before' => null, 'after' => Site::find(1)->notes], $event->payload['changes'][0]);
        $this->assertSame([1, 2], $event->payload['stop_ids']);
        $this->assertSame(Site::find(1)->notes, app(DriverJourneyReadModel::class)->load(Journey::find(1))->orders->first()->site->notes);
        // The site permission remains scoped: no full anagraphic edit for the driver.
        $this->putJson('/api/site/updateBooleans/1', ['name' => 'Non autorizzato'])->assertForbidden();
    }

    public function test_conflicting_edits_keep_the_first_save_and_preserve_notes_from_other_clients(): void
    {
        $this->start();
        $version = JourneySiteNotes::version(null);
        $this->saveNotes('Prima nota.', $version)->assertOk();
        $this->saveNotes('Sovrascrittura obsoleta.', $version)->assertConflict();
        $this->assertSame('Prima nota.', Site::find(1)->notes);
        $this->assertSame(1, DB::table('journey_events')->where('payload->event', 'site_notes_updated')->count());
        $current = JourneySiteNotes::version('Prima nota.');
        $this->saveNotes('Prima nota.', $current)->assertOk();
        $this->assertSame(1, DB::table('journey_events')->where('payload->event', 'site_notes_updated')->count());
        $this->saveNotes(null, $current)->assertOk()->assertJsonPath('journey.orders.0.site.notes', null);
    }

    public function test_only_assigned_driver_or_control_roles_can_edit_sedes_belonging_to_the_execution(): void
    {
        $this->start();
        $this->login(99);
        $this->saveNotes('No')->assertForbidden();
        $this->login(1, UserRole::WAREHOUSE_WORKER);
        $this->saveNotes('No')->assertForbidden();
        $this->login(1, UserRole::LOGISTIC);
        $this->saveNotes('Aggiornamento logistica.')->assertOk();
        DB::table('sites')->insert(['id' => 2, 'name' => 'Sede esterna', 'customer_id' => 1]);
        $this->saveNotes('No', null, 2)->assertNotFound();
        $this->assertNull(Site::find(2)->notes);
    }

    public function test_before_departure_after_finish_and_released_sites_are_not_editable(): void
    {
        $this->saveNotes('No')->assertForbidden();
        $this->start();
        $this->command('skip', ['stop_id' => 1, 'reason_code' => 'customer_closed']);
        // Same site still belongs to stop 2, so it is editable until that order is also released.
        $this->saveNotes('Sede ancora nel viaggio.')->assertOk();
        $this->command('skip', ['stop_id' => 2, 'reason_code' => 'customer_closed']);
        $this->saveNotes('No')->assertNotFound();
        $this->command('finish', []);
        $this->saveNotes('No')->assertForbidden();
    }

    public function test_validation_deleted_sites_and_history_visibility(): void
    {
        $this->start();
        $this->saveNotes(str_repeat('x', 5001))->assertUnprocessable();
        $this->putJson('/api/journeys/1/sites/1/notes', ['notes' => 'Test', 'notes_version' => JourneySiteNotes::version(null), 'name' => 'Sostituito'])->assertUnprocessable();
        $this->saveNotes('Pedana installata.')->assertOk();
        $this->getJson('/api/journeys/1/execution/history')->assertForbidden();
        $this->login(1, UserRole::LOGISTIC);
        $this->getJson('/api/journeys/1/execution/history')->assertOk()->assertJsonPath('events.0.payload.operation', 'update_site_notes');
        Site::find(1)->delete();
        $this->saveNotes('No')->assertNotFound();
        $site = app(DriverJourneyReadModel::class)->load(Journey::find(1))->orders->first()->site;
        $this->assertFalse($site->notes_editable);
    }
}
