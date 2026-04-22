<?php

namespace MiniQL\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MiniQL\Exceptions\MiniQLException;
use MiniQL\Facades\MiniQL;
use MiniQL\Schema\SchemaRegistry;

class MiniQLController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (empty($payload['query']) && empty($payload['mutation'])) {
            return response()->json([
                'data'   => null,
                'errors' => [['message' => 'Request must contain a [query] or [mutation] key.']],
            ], 400);
        }

        try {
            $result = MiniQL::execute($payload);

            $status = empty($result['errors']) ? 200 : 207;

            return response()->json($result, $status);

        } catch (MiniQLException $e) {
            return response()->json([
                'data'   => null,
                'errors' => [['message' => $e->getMessage()]],
            ], 422);

        } catch (\Throwable $e) {
            $exposeTrace = config('miniql.errors.expose_trace', false);

            if (config('miniql.errors.log_errors', true)) {
                logger()->error('[MiniQL] ' . $e->getMessage(), ['exception' => $e]);
            }

            return response()->json([
                'data'   => null,
                'errors' => [[
                    'message' => config('miniql.errors.expose_sql', false)
                        ? $e->getMessage()
                        : 'An internal error occurred.',
                    'trace' => $exposeTrace ? $e->getTraceAsString() : null,
                ]],
            ], 500);
        }
    }

    /**
     * Introspection endpoint — returns schema as JSON.
     */
    public function schema(SchemaRegistry $registry): JsonResponse
    {
        if (!config('miniql.introspection.enabled', true)) {
            abort(403, 'Schema introspection is disabled.');
        }

        return response()->json([
            'schema'  => $registry->introspect(),
            'version' => '1.0',
        ]);
    }
}
