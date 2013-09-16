<?php
/*
 * We do it this way because, although PHP has a great many OOP features,
 * it has some unflexibilities that making sweeping changes more difficult
 * than they have to be.
 * 
 * Because the codebase is moving so fast, and there are so many small changes,
 * I prefer to use a kind of mutable snippet format for injecting features into
 * functions.
 */
$self = new stdClass();
$self->settings = static::getModelSettings();
$self->attributes_table = array_merge(static::getModelAttributes(),static::getMetaAttributes());
$self->model_attributes_table = static::getModelAttributes();
$self->meta_attributes_table = static::getMetaAttributes();