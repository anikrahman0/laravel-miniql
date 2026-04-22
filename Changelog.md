# Changelog

All notable changes to MiniQL will be documented in this file.

## [1.0.0] - 2026-04-22

### Added
- Initial release
- Config-driven schema (models, fields, relations, mutations)
- Single POST endpoint query engine
- N+1-safe eager loading with nested relation constraints
- Advanced `where`, `whereIn`, `whereNull`, `whereNotNull`, `search` filters
- Pagination with meta (total, lastPage, etc.)
- Mutation system with DB transactions and Laravel validation
- Resolver injection layer (custom query builders per model)
- Hook lifecycle (before/after query and mutation)
- Redis-backed query caching with auto-invalidation
- Schema introspection endpoint (`GET /api/miniql/schema`)
- Rate limiting middleware
- Fluent PHP API (`MiniQL::query('users')->where()->get()`)
- Artisan commands: `make-resolver`, `make-mutation`, `schema-dump`, `schema-validate`
- PHPUnit test suite via Orchestra Testbench