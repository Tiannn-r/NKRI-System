import os
import sys

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, SCRIPT_DIR)

from push_part15_data import find_database, push_part15

if __name__ == "__main__":
    db_path = find_database()
    print("Menjalankan import Part 15 ke database...")
    push_part15(db_path, reset_mode=False)
