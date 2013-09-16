<?php
class OrderByValidator {
  public function validate( $source, &$value, &$result ) {
    JSONAPIHelpers::debug("Beggining validation of OrderBy");
    $result->addWarning("Looks like I can edit things",-1);
  }
}