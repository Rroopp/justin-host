import json
import os

base_plan = 'testsprite_tests/testsprite_frontend_test_plan.json'
new_plan = 'testsprite_tests/phase3_expanded_test_plan.json'

# Use absolute paths or relative to strict root
# Assuming CWD is project root
if not os.path.exists(base_plan):
    print(f"Error: Base plan {base_plan} not found.")
    exit(1)

if not os.path.exists(new_plan):
    print(f"Error: New plan {new_plan} not found.")
    exit(1)

try:
    with open(base_plan, 'r') as f:
        base = json.load(f)
except json.JSONDecodeError:
    print("Error decoding base plan")
    base = []

try:
    with open(new_plan, 'r') as f:
        new_items = json.load(f)
except json.JSONDecodeError:
    print("Error decoding new plan")
    exit(1)

existing_ids = {item.get('id') for item in base if 'id' in item}
to_add = []
for item in new_items:
    if item.get('id') not in existing_ids:
        to_add.append(item)
    else:
        print(f"Skipping duplicate ID: {item.get('id')}")

base.extend(to_add)

with open(base_plan, 'w') as f:
    json.dump(base, f, indent=4)

print(f"Successfully merged {len(to_add)} new tests into {base_plan}")
