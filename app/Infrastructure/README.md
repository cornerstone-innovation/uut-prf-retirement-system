# Infrastructure Layer

This layer handles external systems and technical implementation details.

Examples:
- ClickPesa API client
- S3/MinIO storage services
- notification providers
- persistence-heavy services
- export/report generators

Rules:
- isolate external dependencies from the domain layer
- do not place core financial truth here