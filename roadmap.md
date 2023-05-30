- [ ] Signed URLs and Validation for upload and previewing files.
- [ ] Implement File Download.
- [ ] Provide Sorting Trait
- [ ] Provide Test Base Class and tests.
- [ ] Provide a way to add custom middlewares.
- [ ] Defer model by default.
- [ ] Explore Validation and ErrorBag improvements.
- [ ] Consider implementing Turbolinks.


## Allowed Twig methods in classes to be added to settings.php

```php
$settings['twig_sandbox_allowed_classes'] = [
  '\Drupal\wire\TemporaryUploadedFile',
  '\Illuminate\Support\MessageBag'
];
```
