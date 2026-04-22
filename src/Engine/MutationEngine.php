<?php

namespace MiniQL\Engine;

use MiniQL\Schema\SchemaRegistry;
use MiniQL\Schema\SchemaValidator;
use MiniQL\Exceptions\MutationException;
use Illuminate\Support\Facades\DB;

class MutationEngine
{
    public function __construct(
        protected SchemaRegistry  $registry,
        protected SchemaValidator $validator
    ) {}

    /**
     * Execute a full mutation payload.
     * Each key is a registered mutation name.
     */
    public function execute(array $mutations): array
    {
        $result = [];

        DB::beginTransaction();

        try {
            foreach ($mutations as $name => $node) {
                $result[$name] = $this->runMutation($name, $node);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new MutationException($e->getMessage(), 0, $e);
        }

        return $result;
    }

    protected function runMutation(string $name, array $node): mixed
    {
        $globalMutations = $this->registry->getGlobalMutations();

        if (!isset($globalMutations[$name])) {
            throw new MutationException("Unknown mutation: [{$name}].");
        }

        $handlerClass = $globalMutations[$name]['class'];
        $type         = $globalMutations[$name]['type'];
        $config       = $this->registry->get($type);

        // Run before_mutation hook
        $this->runHook($config, 'before_mutation', $node);

        $handler = app($handlerClass);

        if (!method_exists($handler, 'handle')) {
            throw new MutationException("Mutation handler [{$handlerClass}] must have a handle() method.");
        }

        $result = $handler->handle($node);

        // Run after_mutation hook
        $this->runHook($config, 'after_mutation', $result);

        return $result;
    }

    protected function runHook(array $config, string $hookName, mixed &$context): void
    {
        $hookClass = $config['hooks'][$hookName] ?? null;

        if ($hookClass && class_exists($hookClass)) {
            app($hookClass)->handle($context);
        }
    }
}
