# Database Encoding Fix Plan (UTF-8 Migration & Repair)

**Goal:** Ensure all relevant database tables and columns consistently use `utf8mb4` and repair any existing data corrupted by previous connection character set mismatches.

**Context:**
*   GitHub Issue [#38](https://github.com/kawf/kawf/issues/38) initially suggested database tables used `latin1` collation.
*   Verification (`SHOW CREATE TABLE u_users`) revealed that the `u_users` table and its `signature` column are **already correctly configured** as `utf8mb4 COLLATE utf8mb4_unicode_ci`.
*   However, the database connection DSN in `include/sql.inc` **did not** explicitly specify `charset=utf8mb4` until it was recently added.
*   Debugging showed UTF-8 characters (like curly quotes " ") being stored incorrectly (as Mojibake, e.g., `\xc3\xa2...`) when saved through the *old* connection, even into the `utf8mb4` column.
*   Fixing the connection (`charset=utf8mb4`) allows **new** data to be saved correctly, but **historically corrupted data remains** in the database.
*   This issue likely affects **any text field** where UTF-8 characters were saved via the old connection.

**Related PHP Code Changes:**
*   **`utf8ize()` function removed:** The call to `utf8ize()` within `stripcrap()` (in `include/strip.inc`) has been removed.
    *   **Reason:** This function was a workaround for the legacy connection encoding issues. Its internal check (`is_valid_utf8()`) appeared to incorrectly flag valid modern UTF-8 sequences (like 4-byte emojis) as invalid. It then attempted a faulty conversion (`mb_convert_encoding` from `ISO-8859-1`) which corrupted the valid input data before it could be saved.
    *   Since the database connection now correctly uses `utf8mb4`, this workaround is unnecessary and was actively causing data corruption for valid UTF-8 input. Removing it allows correct handling of submitted UTF-8 data.
*   **`remoronize()` function:** This function (also in `strip.inc`) attempts to fix legacy character issues (like smart quotes) using non-multibyte-safe `str_replace`. It remains in `stripcrap()` for now but should be reviewed later for necessity and potential UTF-8 safety issues.

**Steps:**

1.  **Backup:** Perform a full database backup before attempting any schema changes or data repair.
2.  **Verify Schema for OTHER Tables:** Confirm the `CHARACTER SET` and `COLLATION` for other text-heavy tables/columns. While `u_users` is correct, others might still be `latin1`.
    *   Primary candidates: `f_messages.message`, `f_messages.subject`, `f_threads.subject` (if separate), `f_forums.name`, `f_forums.description`, `f_gmsgs` (?).
    *   Use `SHOW CREATE TABLE <tablename>;` for each.
3.  **Confirm Connection:** Ensure the PHP connection DSN in `include/sql.inc` **includes `charset=utf8mb4`**. (This is done).
4.  **Convert OTHER Tables/Columns (If Necessary):** If Step 2 reveals other tables/columns are still `latin1`, convert them.
    *   Execute `ALTER TABLE` commands.
    *   Example for `f_messages` if it were `latin1`:
        ```sql
        -- Convert database default first if possible/desired
        -- ALTER DATABASE kawf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

        -- Then convert table (attempts to convert all string columns)
        ALTER TABLE f_messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        -- Add other tables like f_threads, f_forums if needed
        ```
    *   Use `CONVERT TO` to attempt conversion of existing data during the schema change.
5.  **Repair Existing Corrupted Data:**
    *   Data saved *before* the connection charset was fixed might still contain Mojibake (UTF-8 bytes misinterpreted as `latin1` during the original INSERT/UPDATE, even into `utf8mb4` columns).
    *   Identify rows with corrupted data (e.g., searching for specific byte sequences like `0xC3A2` which corresponds to `â` in relevant text columns like `signature`, `message`, `subject`, etc.).
    *   Develop and test scripts to repair the data. This often involves reading the corrupted data, converting it correctly in PHP (e.g., `mb_convert_encoding($corrupted_data, 'UTF-8', 'ISO-8859-1')` or similar, depending on the exact nature of corruption), and writing it back.
    *   **Example Repair Concept (PHP - adapt per column):**
        ```php
        // Fetch potentially bad signatures
        $sth = db_query("SELECT aid, signature FROM u_users WHERE signature LIKE '%\xc3\xa2%' OR ... other patterns ...");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $aid = $row['aid'];
            $corrupted_text = $row['signature'];
            // Attempt repair (specific conversion depends on analysis)
            // This conversion assumes UTF-8 bytes were stored directly into a latin1 connection stream
            $repaired_text = mb_convert_encoding($corrupted_text, 'UTF-8', 'ISO-8859-1');
            if ($repaired_text !== $corrupted_text) { // Only update if changed
                // Adapt UPDATE statement for correct table/column/id
                db_exec("UPDATE u_users SET signature = ? WHERE aid = ?", array($repaired_text, $aid));
                error_log("Repaired signature for AID: " . $aid);
            }
        }
        // Repeat for f_messages.message, f_messages.subject, etc.
        ```
6.  **Test Application:** Thoroughly test areas that display/use the repaired fields (signatures, posts, subjects, forum names) to ensure correct rendering and that new data is saved correctly with the fixed connection.

**Considerations:**
*   Data repair can be complex and requires careful testing to ensure the correct conversion is applied.
*   Database schema changes (if needed for other tables) require downtime or careful online migration strategy.
*   Needs thorough testing after any changes.
