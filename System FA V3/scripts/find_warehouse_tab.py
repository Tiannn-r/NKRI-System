with open(r"d:\system SN - Copy\static\index.html", "r", encoding="utf-8") as f:
    for i, line in enumerate(f):
        if "warehouse" in line.lower() and "tab" in line.lower():
            print(f"{i+1}: {line.strip()[:100]}")
