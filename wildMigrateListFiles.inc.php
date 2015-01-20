<?php

class wildMigrateListFiles extends MigrateListFiles {

  public function __construct($list_dirs, $base_dir, $file_mask = NULL, $options = array(), MigrateContentParser $parser = NULL) {
    parent::__construct($list_dirs, $base_dir, $file_mask, $options, $parser);
  }
   /**
   * Retrieve a list of files based on parameters passed for the migration.
   */
  public function getIdList() {
    $files = array();
    foreach ($this->listDirs as $dir) {
      migrate_instrument_start("Retrieve $dir");
      $files = array_merge(file_scan_directory($dir, $this->fileMask, $this->directoryOptions), $files);

      migrate_instrument_stop("Retrieve $dir");
    }

    //reorder files here: so /something/index.html comes before /something/notindex.html which comes before /something/longer/index.html 
    $newf = array();
    foreach ($files as $file) {
      $count = 10*count(explode("/", $file->uri));
      if ($file->filename == "index.html") {
        $count--; //prioritise index files
      }
      $newf[$count][] = $file;
    }
    ksort($newf);

    foreach ($newf as $value) {
      foreach ($value as $file) {
        $retf[$file->uri] = $file;
      }
    }

    if (isset($retf)) {
      return $this->getIDsFromFiles($retf);
    }
    Migration::displayMessage(t('Loading of !listuri failed:', array('!listuri' => $this->listUri)));
    return NULL;
  }


}