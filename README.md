CheckKit is set of data validating tools for Fruit framework.

# Synopsis

```php
$repo = Repo::default();
$repo->mustCheck([], 'array', []); // safe
$repo->mustCheck([], 'array', ['min_length' => 3]); // throws exception
```

See documents of each validator for supported rules.

# License

Any version of MIT, GPL or LGPL.
