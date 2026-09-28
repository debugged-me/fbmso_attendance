# Tool Output Policy

Never reproduce large raw tool outputs in the response.

For commands such as:

- rg
- grep
- sed
- nl
- find
- git diff
- test output

summarize the result instead.

Example:

Bad:
<full 100-line rg output>

Good:
Found 9 uses of `is_accounting_writer()` and 6 uses of `is_auditor()`.
The relevant permission checks are in `MobileMisc.php` around lines 1346-1354 and 1567-1827.
