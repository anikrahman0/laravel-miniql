<?php

namespace MiniQL\Exceptions;

class MiniQLException extends \RuntimeException {}

class SchemaException extends MiniQLException {}

class ValidationException extends MiniQLException {}

class QueryException extends MiniQLException {}

class MutationException extends MiniQLException {}
