# UUT PRF Core Schema Outline

## Identity Domain
- users
- roles
- permissions
- model_has_roles
- model_has_permissions

## Investor Domain
- investors
- investor_contacts
- investor_addresses
- investor_nominees
- investor_kyc_profiles

## Product Domain
- products
- plans
- plan_options
- lock_in_rules
- switch_rules
- fee_rules

## Payment Domain
- payment_requests
- payment_transactions
- payment_callbacks
- payment_reconciliations

## Purchase Domain
- purchase_orders
- purchase_allocations
- contract_notes

## NAV Domain
- nav_records
- nav_publications
- nav_audit_logs

## Ledger Domain
- unit_lots
- ledger_entries
- lot_movements
- balance_snapshots

## Holding Domain
- holding_views
- holding_valuations

## Switching Domain
- switch_requests
- switch_allocations

## Redemption Domain
- redemption_requests
- redemption_allocations
- redemption_approvals

## Payout Domain
- payout_instructions
- payout_attempts
- payout_confirmations

## Document Domain
- documents
- document_versions
- document_links

## Audit Domain
- audit_logs
- approval_logs
- system_events

## Notification Domain
- notification_logs
- notification_templates

## Reconciliation Domain
- reconciliation_cases
- reconciliation_items