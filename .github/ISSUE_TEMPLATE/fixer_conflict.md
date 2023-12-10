---
name: Fixer Conflict report
about: Create an issue to report a fixer conflict
title: ''
labels: ['Status: triage', 'Type: bug', 'Focus: Fixer Conflicts']
assignees: ''

---

<!--
Before reporting a sniff related bug, please check the error code using `phpcs -s`.

If the error code starts with anything other than `Generic`, `MySource`, `PEAR`,
`PSR1`, `PSR2`, `PSR12`, `Squiz` or `Zend`, the error is likely coming from an
external PHP_CodeSniffer standard.

Please report bugs for externally maintained sniffs to the appropriate external
standard repository (not here).
-->

## Describe the bug
A clear and concise description of what the bug is.

_This issue was detected via the automated fixer conflict check running in CI._

PR through which the fixer conflict was detected: #...

### Code sample
```php
echo "A short code snippet that can be used to reproduce the fixer conflict. Do NOT paste screenshots of code!";
```

### To reproduce
Steps to reproduce the behavior:
1. Create a file called `test.php` with the code sample above...
2. Run `phpcbf test.php ...`
3. The run will result in the file being marked as "FAILED TO FIX"

## Fixer conflict details
<!-- Run `phpcbf over the file with the -vv flag and paste the output of the last few "loops" here. -->`
```
```

## Versions (please complete the following information)

| | |
|-|-|
| Operating System | [e.g., Windows 10, MacOS 10.15]
| PHP version | [e.g., 7.2, 8.1]
| PHP_CodeSniffer version | [e.g., 3.7.2, master]
| Standard | [e.g., PSR2, PSR12, Squiz, custom]
| Install type | [e.g. Composer (global/local), PHAR, git clone, other (please expand)]

## Additional context
Add any other context about the problem here.

## Please confirm:

- [ ] I have searched the issue list and am not opening a duplicate issue.
- [ ] I confirm that this bug is a bug in PHP_CodeSniffer and not in one of the external standards.
- [ ] I have verified the issue still exists in the `master` branch of PHP_CodeSniffer.
