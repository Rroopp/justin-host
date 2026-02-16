<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ProductAttribute;

class MedicalInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder is idempotent and safe to run multiple times.
     */
    public function run(): void
    {
        // ==========================================
        // 1. DEFINE ATTRIBUTES (Global)
        // ==========================================

        // -- Material Attribute --
        $material = ProductAttribute::firstOrCreate(['slug' => 'material'], [
            'name' => 'Material', 
            'type' => 'select', 
            'is_required' => true
        ]);
        foreach (['Titanium', 'Stainless Steel', 'Peek', 'Cobalt Chrome', 'Aluminium', 'Carbon Fiber', 'Neoprene', 'Elastic', 'Plastic', 'Foam', 'Plaster of Paris', 'Fiberglass'] as $val) {
            $material->options()->firstOrCreate(['value' => $val]);
        }

        // -- Side Attribute --
        $side = ProductAttribute::firstOrCreate(['slug' => 'side'], [
            'name' => 'Side', 
            'type' => 'select', 
            'is_required' => true
        ]);
        foreach (['Left', 'Right', 'Universal', 'Bilateral'] as $val) {
            $side->options()->firstOrCreate(['value' => $val]);
        }

        // -- Dimension Attributes --
        $holes = ProductAttribute::firstOrCreate(['slug' => 'holes'], ['name' => 'Holes', 'type' => 'number', 'unit' => 'holes']);
        $length = ProductAttribute::firstOrCreate(['slug' => 'length'], ['name' => 'Length', 'type' => 'number', 'unit' => 'mm']);
        $diameter = ProductAttribute::firstOrCreate(['slug' => 'diameter'], ['name' => 'Diameter', 'type' => 'number', 'unit' => 'mm']);

        // -- Clinical Attributes --
        $sterility = ProductAttribute::firstOrCreate(['slug' => 'sterility'], ['name' => 'Sterility', 'type' => 'select']);
        foreach (['Sterile', 'Non-Sterile'] as $val) {
            $sterility->options()->firstOrCreate(['value' => $val]);
        }

        $texture = ProductAttribute::firstOrCreate(['slug' => 'texture'], ['name' => 'Texture', 'type' => 'select']);
        foreach (['Powdered', 'Powder-Free', 'Textured'] as $val) {
            $texture->options()->firstOrCreate(['value' => $val]);
        }

        $gloveMaterial = ProductAttribute::firstOrCreate(['slug' => 'glove_material'], ['name' => 'Glove Material', 'type' => 'select']);
        foreach (['Latex', 'Nitrile', 'Vinyl'] as $val) {
            $gloveMaterial->options()->firstOrCreate(['value' => $val]);
        }
        
        $userGroup = ProductAttribute::firstOrCreate(['slug' => 'user_group'], ['name' => 'User Group', 'type' => 'select']);
        foreach (['Adult', 'Pediatric', 'Universal'] as $val) {
            $userGroup->options()->firstOrCreate(['value' => $val]);
        }


        // ==========================================
        // 2. DEFINE CATEGORIES & SUBCATEGORIES
        // ==========================================

        // Helper to seed subcategories
        $seedSubcategories = function($category, $subs) {
             foreach($subs as $sub) {
                 Subcategory::firstOrCreate(
                     ['name' => $sub, 'category_id' => $category->id]
                 );
             }
        };

        // -- Orthopaedic Plates --
        $catPlate = Category::firstOrCreate(['name' => 'Orthopaedic Plates'], ['description' => 'Bone plates']);
        $catPlate->attributes()->syncWithoutDetaching([
            $material->id => ['sort_order' => 1],
            $side->id => ['sort_order' => 2],
            $holes->id => ['sort_order' => 3],
            $length->id => ['sort_order' => 4],
            $sterility->id => ['sort_order' => 5],
        ]);
        $seedSubcategories($catPlate, ['Locking Plate', 'Compression Plate', 'Reconstruction Plate', 'Distal Radius Plate']);

        // -- Orthopaedic Nails --
        $catNail = Category::firstOrCreate(['name' => 'Orthopaedic Nails'], ['description' => 'IM Nails']);
        $catNail->attributes()->syncWithoutDetaching([
            $material->id => ['sort_order' => 1],
            $diameter->id => ['sort_order' => 2],
            $length->id => ['sort_order' => 3],
            $side->id => ['sort_order' => 4],
        ]);
        $seedSubcategories($catNail, ['Femoral Nail', 'Tibial Nail', 'Humeral Nail', 'PFN', 'Elastic Nail']);

        // -- Orthopaedic Screws --
        $catScrew = Category::firstOrCreate(['name' => 'Orthopaedic Screws'], ['description' => 'Bone screws']);
        $catScrew->attributes()->syncWithoutDetaching([
            $material->id => ['sort_order' => 1],
            $diameter->id => ['sort_order' => 2],
            $length->id => ['sort_order' => 3],
        ]);
        $seedSubcategories($catScrew, ['Cortical Screw', 'Cancellous Screw', 'Locking Screw', 'Cannulated Screw']);

        // -- Exam Gloves --
        $catGloves = Category::firstOrCreate(['name' => 'Exam Gloves'], ['description' => 'Medical gloves']);
        $catGloves->attributes()->syncWithoutDetaching([
             $gloveMaterial->id => ['sort_order' => 1],
             $texture->id => ['sort_order' => 2],
             $sterility->id => ['sort_order' => 3],
        ]);
        $seedSubcategories($catGloves, ['Examination Gloves', 'Surgical Gloves']);
        
        // -- Sutures --
        $catSutures = Category::firstOrCreate(['name' => 'Sutures'], ['description' => 'Surgical sutures']);
        $catSutures->attributes()->syncWithoutDetaching([
            $sterility->id => ['sort_order' => 1],
        ]);
        $seedSubcategories($catSutures, ['Vicryl', 'Prolene', 'Silk', 'Nylon', 'PDS']);

        // -- Disposables --
        $catDisposables = Category::firstOrCreate(['name' => 'Disposables'], ['description' => 'Single-use items']);
        $catDisposables->attributes()->syncWithoutDetaching([
            $sterility->id => ['sort_order' => 1],
        ]);
        $seedSubcategories($catDisposables, ['Syringes', 'Needles', 'Gauze', 'Bandages', 'Drapes']);

        // -- Walking Aids --
        $catWalking = Category::firstOrCreate(['name' => 'Walking Aids'], ['description' => 'Mobility aids']);
        $catWalking->attributes()->syncWithoutDetaching([
            $material->id => ['sort_order' => 1],
            $userGroup->id => ['sort_order' => 2],
        ]);
        $seedSubcategories($catWalking, ['Crutches', 'Walking Frames', 'Walking Sticks', 'Rollators', 'Wheelchairs']);

        // -- Braces & Supports --
        $catBraces = Category::firstOrCreate(['name' => 'Braces & Supports'], ['description' => 'Orthoses']);
        $catBraces->attributes()->syncWithoutDetaching([
            $side->id => ['sort_order' => 1],
            $material->id => ['sort_order' => 2],
            $userGroup->id => ['sort_order' => 3],
        ]);
        $seedSubcategories($catBraces, ['Knee Brace', 'Ankle Support', 'Wrist Splint', 'Lumbar Corset', 'Cervical Collar', 'Shoulder Immobilizer']);

        // -- Splints & Casting --
        $catCasting = Category::firstOrCreate(['name' => 'Splints & Casting'], ['description' => 'Immobilization']);
        $catCasting->attributes()->syncWithoutDetaching([
             $material->id => ['sort_order' => 1], 
        ]);
        $seedSubcategories($catCasting, ['Plaster of Paris', 'Fiberglass Cast', 'Cast Padding', 'Stockinette']);

        // -- Surgical Sets --
        $catSets = Category::firstOrCreate(['name' => 'Surgical Sets'], ['description' => 'Instrument sets']);
        $seedSubcategories($catSets, ['Large Frag Set', 'Small Frag Set', 'Basic Instrument Set', 'DHS/DCS Set']);

        // -- General Consumables --
        $catConsumables = Category::firstOrCreate(['name' => 'Consumables'], ['description' => 'General consumables']);
        $catConsumables->attributes()->syncWithoutDetaching([
            $sterility->id => ['sort_order' => 1],
        ]);
        $seedSubcategories($catConsumables, ['General']);
    }
}
