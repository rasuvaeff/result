# Examples

Run examples from the package root after installing dependencies:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/basic.php
```

| Script | Shows | Needs server? |
|---|---|---|
| `basic.php` | `Result`, `Option`, `fromThrowable()`, and `toResult()` usage | No |
