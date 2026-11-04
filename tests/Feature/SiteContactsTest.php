<?php

namespace Tests\Feature;

use App\Enums\InternalContactRole;
use App\Enums\UserRole;
use App\Models\InternalContact;
use App\Models\Journey;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use Illuminate\Support\Facades\DB;
use Tests\Support\JourneyExecutionTestCase;

class SiteContactsTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->login(UserRole::LOGISTIC);
    }

    private function login(UserRole $role, bool $admin = false): void
    {
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => $role, 'is_admin' => $admin, 'email_verified_at' => now()]));
    }

    public function test_create_update_and_fixed_category_values(): void
    {
        $this->assertCount(4, InternalContactRole::options());
        $contact = $this->postJson('/api/sites/1/contacts', ['name' => 'Mario', 'surname' => 'Rossi', 'mobile' => '+39 333 1234567', 'role' => InternalContactRole::INTERMEDIARIO->value, 'site_id' => 999])
            ->assertCreated()->assertJsonPath('contact.site_id', 1)->assertJsonPath('contact.role', 'inrtermediario')->json('contact');
        $this->putJson('/api/sites/1/contacts/'.$contact['id'], ['name' => 'Mario', 'role' => 'magazzino', 'phone' => '0422 123456', 'mobile' => null])
            ->assertOk()->assertJsonPath('contact.role', 'magazzino')->assertJsonPath('contact.mobile', null);
        $this->assertSame('0422 123456', InternalContact::find($contact['id'])->phone);
    }

    public function test_invalid_categories_and_contact_data_are_rejected_without_writes(): void
    {
        $this->postJson('/api/sites/1/contacts', ['name' => '', 'role' => 'inventata', 'email' => 'invalid', 'mobile' => 'javascript:123'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'role', 'email', 'mobile']);
        $this->assertSame(0, InternalContact::count());
    }

    public function test_category_is_required_for_creation_and_update_of_uncategorized_contacts(): void
    {
        $contact = InternalContact::create(['site_id' => 1, 'name' => 'Senza categoria']);
        foreach ([[], ['role' => null], ['role' => '']] as $input) {
            $this->postJson('/api/sites/1/contacts', ['name' => 'Nuovo'] + $input)
                ->assertUnprocessable()->assertJsonValidationErrors('role');
            $this->putJson('/api/sites/1/contacts/'.$contact->id, ['name' => 'Modificato'] + $input)
                ->assertUnprocessable()->assertJsonValidationErrors('role');
        }
        $this->assertSame(1, InternalContact::count());
        $this->assertSame('Senza categoria', $contact->fresh()->name);
    }

    public function test_update_cannot_move_or_edit_a_contact_of_another_site(): void
    {
        DB::table('sites')->insert(['id' => 2, 'customer_id' => 1, 'name' => 'Altra sede']);
        $contact = InternalContact::create(['site_id' => 2, 'name' => 'Altro referente']);
        $this->putJson('/api/sites/1/contacts/'.$contact->id, ['name' => 'Modificato'])->assertNotFound();
        $this->assertSame('Altro referente', $contact->fresh()->name);
    }

    public function test_permission_matrix_for_creation_and_editing(): void
    {
        $contact = InternalContact::create(['site_id' => 1, 'name' => 'Referente']);
        foreach ([[UserRole::DRIVER, false, 403], [UserRole::WAREHOUSE_MANAGER, false, 403], [UserRole::MANAGER, false, 201], [UserRole::DEVELOPER, false, 201], [UserRole::DRIVER, true, 201]] as [$role, $admin, $status]) {
            $this->login($role, $admin);
            $this->postJson('/api/sites/1/contacts', ['name' => 'Nuovo', 'role' => 'magazzino'])->assertStatus($status);
            $this->putJson('/api/sites/1/contacts/'.$contact->id, ['name' => 'Aggiornato', 'role' => 'segreteria'])->assertStatus($status === 201 ? 200 : 403);
        }
    }

    public function test_active_driver_phonebook_updates_without_changing_the_baseline_or_execution(): void
    {
        $contact = InternalContact::create(['site_id' => 1, 'name' => 'Mario', 'mobile' => '3331234567']);
        foreach ([1, 2] as $id) {
            DB::table('orders')->where('id', $id)->update(['documents_input_hash' => app(\App\Services\JourneyExecution\DocumentRevision::class)->hash(\App\Models\Order::findOrFail($id))]);
        }
        $this->start();
        $journey = Journey::find(1);
        $checksum = $journey->baseline->checksum;
        $executionData = $journey->orderExecutions()->first()->data;
        $this->putJson('/api/sites/1/contacts/'.$contact->id, ['name' => 'Mario', 'mobile' => '3337654321', 'role' => 'smaltimenti'])->assertOk();
        $order = app(DriverJourneyReadModel::class)->load($journey)->orders->first();
        $this->assertSame('3337654321', $order->site->internalContacts->first()->mobile);
        $this->assertSame('smaltimenti', $order->site->internalContacts->first()->toArray()['role']);
        $this->assertSame($checksum, $journey->baseline->fresh()->checksum);
        $this->assertSame($executionData, $journey->orderExecutions()->first()->data);
    }
}
