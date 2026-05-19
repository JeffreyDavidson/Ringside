# Troubleshooting

## Common Test Issues
- **Memory Issues**: Use `--memory-limit=512M` for large test suites
- **Parallel Issues**: Some tests may not be parallel-safe
- **Database Issues**: Ensure proper database cleanup
- **Timing Issues**: Use `testTime()->freeze()` for time-sensitive tests

## Debug Techniques
```bash
# Run single test with debug info
./vendor/bin/pest tests/Unit/Models/WrestlerTest.php --stop-on-failure

# Show test coverage
./vendor/bin/pest --coverage --min=100

# Profile test performance
./vendor/bin/pest --profile
```
