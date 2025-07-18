# Performance & Optimization

## Test Performance
- **Fast Execution**: Full test suite should run under 30 seconds
- **Parallel Testing**: Use `--parallel` flag for speed
- **Memory Management**: Monitor memory usage in long test runs
- **Database Optimization**: Use in-memory SQLite for speed

## Test Reliability
- **No Flaky Tests**: All tests must be deterministic
- **Proper Cleanup**: Clean up after each test
- **Isolated Tests**: Tests should not depend on each other
- **Consistent Results**: Same results every time
