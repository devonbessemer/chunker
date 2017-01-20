# Chunker

Allows you to chunk incrementing models, make modifications, add records, and delete records without affecting the chunkable dataset.

This fixes the consistency issue when you delete or make major changes to records in your callback within Eloquent's chunk method but requires an incrementing model (auto-increment primary key).