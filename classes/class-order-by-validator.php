<?php
class OrderByValidator {
  public function validate( $source, &$value, &$target ) {
    RedEHelpers::debug("Beggining validation of OrderBy");
    $target->addWarning("Looks like I can edit things",-1);
  }
}