#!/bin/bash

# Script to add Auditable trait to all models

MODELS_DIR="app/Models"

# List of models to add the trait to
MODELS=(
    "Customer.php"
    "Supplier.php"
    "PosSale.php"
    "PurchaseOrder.php"
    "Staff.php"
    "Expense.php"
    "Rental.php"
    "Commission.php"
    "Budget.php"
    "PosSalePayment.php"
    "JournalEntry.php"
    "ChartOfAccount.php"
    "PayrollRun.php"
    "PayrollItem.php"
    "Lpo.php"
    "Package.php"
    "StockTake.php"
    "Asset.php"
    "Category.php"
    "Subcategory.php"
)

for model in "${MODELS[@]}"; do
    file="$MODELS_DIR/$model"
    
    if [ -f "$file" ]; then
        # Check if trait import already exists
        if ! grep -q "use App\\\\Traits\\\\Auditable;" "$file"; then
            # Add import after Model import
            sed -i '/use Illuminate\\Database\\Eloquent\\Model;/a use App\\Traits\\Auditable;' "$file"
        fi
        
        # Add trait to use statement in class
        if grep -q "use HasFactory;" "$file" && ! grep -q "Auditable" "$file"; then
            sed -i 's/use HasFactory;/use HasFactory, Auditable;/' "$file"
        elif grep -q "use HasFactory, SoftDeletes;" "$file" && ! grep -q "Auditable" "$file"; then
            sed -i 's/use HasFactory, SoftDeletes;/use HasFactory, SoftDeletes, Auditable;/' "$file"
        fi
        
        echo "✓ Updated $model"
    else
        echo "✗ $model not found"
    fi
done

echo "Done!"
