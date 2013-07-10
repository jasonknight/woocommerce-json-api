<?php
class OrderByValidator {
  public function validate( $source, &$value, &$target ) {
    JSONAPIHelpers::debug("Beggining validation of OrderBy");
    $target->addWarning("Looks like I can edit things",-1);
  }
}