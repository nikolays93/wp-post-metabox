== How to use ==

```
$Metabox = new Metabox( __('Custom settings', 'Metabox'), true );

$Metabox->set_type('post');
$Metabox->set_field('field_code');
$Metabox->set_content(function(){echo 'test';});
```