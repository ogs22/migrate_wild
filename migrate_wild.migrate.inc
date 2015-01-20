<?php
function migrate_wild_migrate_api() {
  $api = array(
    'api' => 2,
    'groups' => array(
      'wild' => array(
        'title' => t('WILD'),
      	'default_format' => 'filtered_html',
      ),
    ),
    'migrations' => array(
      'MigrateWild' => array(
        'class_name' => 'MigrateWild',
        'group_name' => 'wild',
      )
    ),
  );
  return $api;
}


 


