import re

with open(r"d:\system SN - Copy\static\index.html", "r", encoding="utf-8") as f:
    lines = f.readlines()

print("--- Searching for WO / Supply ---")
for i, line in enumerate(lines):
    if any(k in line.lower() for k in ["generate wo", "buat wo", "wo supply", "supply wo", "supply_modal", "showsupply", "showwo", "womodal"]):
        print(f"L{i+1}: {line.strip()[:100]}")

print("\n--- Searching for all Modals (fixed inset-0 or z-50) ---")
for i, line in enumerate(lines):
    if "fixed inset-0" in line or "z-50 flex items-center" in line:
        print(f"L{i+1}: {line.strip()[:100]}")
