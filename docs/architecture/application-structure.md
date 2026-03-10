# UUT PRF Application Structure

## Architecture Layers

### Http Layer
Handles:
- controllers
- form requests
- API resources
- middleware

Rule:
- no core business logic should live here

### Application Layer
Handles:
- use-case orchestration
- service coordination
- DTOs
- actions

Examples:
- CreatePurchaseAction
- ApproveRedemptionAction
- PublishNavAction

### Domain Layer
Handles:
- business rules
- financial rules
- eligibility rules
- FIFO redemption rules
- lock-in rules
- unit allocation rules

Rule:
- financial truth must be defined here, not in controllers

### Infrastructure Layer
Handles:
- database persistence details
- ClickPesa integration
- S3/MinIO storage
- notification providers
- report exports
- external APIs

## Core Domains
- Identity
- Investor
- Kyc
- Product
- Purchase
- Payment
- Nav
- Ledger
- Holding
- Switching
- Redemption
- Payout
- Document
- Report
- Audit
- Notification
- Reconciliation

## Core Rules
- no financial logic in controllers
- no direct balance editing
- unit holdings must come from append-only ledger logic
- redemption must follow FIFO rules
- settlement and payout are separate from ledger truth
- all sensitive operations must be auditable
- external systems must go through infrastructure services

## Coding Direction
- controllers remain thin
- actions/services coordinate workflows
- domain classes hold financial business rules
- repositories or persistence services isolate database-heavy logic
- integrations are isolated from core business rules