<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Staff;
use App\Models\SurgicalSet;
use App\Models\CaseReservation;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Asset;
use App\Models\PosSale;

class SetDispatchWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_full_dispatch_workflow_and_invoice_creation()
    {
        // 1. Setup User
        $user = Staff::create([
            'full_name' => 'Test Admin',
            'username' => 'test_admin_'.rand(1000,9999), 
            'password' => 'password', // Mutator handles hashing and mapping to password_hash
            'role' => 'admin',
            'email' => 'test@admin.com',
            'status' => 'active'
        ]);
        $this->actingAs($user);

        // 2. Setup Data
        $location = Location::create(['name' => 'Test Storage', 'type' => 'storage']);
        $theatre = Location::create(['name' => 'Test Theatre', 'type' => 'theatre']);
        
        $customer = Customer::create(['name' => 'Test Facility', 'email' => 'test@facility.com']);
        
        // Create Asset (Tray) logic might vary, assuming simple create
        $asset = Asset::create(['name' => 'Tray 1', 'type' => 'tray', 'purchase_date' => now(), 'purchase_price' => 100]);

        // Create Set
        $set = SurgicalSet::create([
            'name' => 'Test Set 1',
            'status' => 'available', // Using string as per logic
            'location_id' => $location->id,
            'asset_id' => $asset->id
        ]);

        // Create Reservation
        $reservation = CaseReservation::create([
            'case_number' => 'CASE-TEST-' . rand(1000,9999),
            'patient_name' => 'John Doe',
            'patient_id' => 'P-12345',
            'surgeon_name' => 'Dr. Smith',
            'procedure_name' => 'Appendectomy',
            'surgery_date' => now()->addHour(),
            'location_id' => $theatre->id,
            'customer_id' => $customer->id,
            'status' => 'confirmed'
        ]);

        // 3. Dispatch Set
        $dispatchRoute = route('dispatch.store', $reservation->id);
        $response = $this->post($dispatchRoute, [
            'surgical_set_id' => $set->id
        ]);
        
        // Follow redirect or just check DB
        if ($response->status() == 302) {
             $response->assertRedirect();
        } else {
             // If manual verify failed, dump
             // $response->dump();
             $response->assertStatus(200); 
        }

        // Assert Set is Attached & Dispatched
        $this->assertDatabaseHas('case_reservation_surgical_set', [
            'case_reservation_id' => $reservation->id,
            'surgical_set_id' => $set->id,
            'status' => 'dispatched'
        ]);

        // 4. Return / Reconcile
        // Update surgery_date to Past to allow return
        $reservation->update(['surgery_date' => now()->subHour()]);

        $reconcileRoute = route('reconcile.store', $reservation->id);
        $response = $this->post($reconcileRoute, [
            'surgical_set_id' => $set->id,
            'notes' => 'All good',
            'instruments' => [] 
        ]);
        
        if ($response->status() == 302) {
             $response->assertRedirect();
        }

        // Assert Pivot Status is 'returned'
        $this->assertDatabaseHas('case_reservation_surgical_set', [
            'case_reservation_id' => $reservation->id,
            'surgical_set_id' => $set->id,
            'status' => 'returned'
        ]);

        // 5. Complete Case (Invoice Generation)
        // Note: complete() in CaseReservationController might require items or stock logic. 
        // If it fails on stock deduction (empty case), we might need to add items.
        // Assuming it handles empty case gracefully or just creates Invoice.
        
        $completeRoute = route('reservations.complete', $reservation->id);
        $response = $this->post($completeRoute);
        
        if ($response->status() == 500) {
            // $response->dump();
        }

        // Assert Invoice Created
        // We look for PosSale with this case number
        $sale = PosSale::where('case_number', $reservation->case_number)->first();
        
        $this->assertNotNull($sale, 'PosSale invoice should be created');
        
        // 6. Verify Patient Data
        $this->assertEquals('John Doe', $sale->patient_name);
        $this->assertEquals('P-12345', $sale->patient_number);
        $this->assertEquals('Dr. Smith', $sale->surgeon_name);
        $this->assertEquals('Test Facility', $sale->facility_name);
        
        // Check inferred Patient Type if we decided to set it
        // $this->assertEquals('Inpatient', $sale->patient_type);
    }
}
