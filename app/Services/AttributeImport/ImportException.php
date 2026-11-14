<?php

namespace App\Services\AttributeImport;

/** Safe, user-facing validation failure, containing no database exception details. */
final class ImportException extends \RuntimeException {}
