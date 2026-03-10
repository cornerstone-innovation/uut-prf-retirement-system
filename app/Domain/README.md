# Domain Layer

This layer contains the core business rules of the UUT PRF system.

Examples:
- FIFO redemption allocation
- lock-in eligibility validation
- unit allocation rules
- NAV application rules
- maturity option rules

Rules:
- no framework-heavy controller logic here
- no external API coupling here
- domain rules must be testable in isolation