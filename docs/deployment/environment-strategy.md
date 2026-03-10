# UUT PRF Environment Strategy

## Environments

### Dev
Purpose:
- active development
- feature testing
- debugging
- local Docker setup

Characteristics:
- debug enabled
- local services
- test credentials
- seeded/sample data

### QA
Purpose:
- internal technical testing
- integration testing
- regression checks

Characteristics:
- stable shared deployment
- debug restricted
- test credentials
- controlled test data

### UAT
Purpose:
- business acceptance testing
- operational validation
- stakeholder review

Characteristics:
- production-like configuration
- controlled access
- realistic workflows
- no experimental code

### Prod
Purpose:
- live operations
- real users
- real transactions
- regulatory reporting

Characteristics:
- debug disabled
- monitoring enabled
- backups enabled
- strict access control
- real credentials

## Deployment Flow
feature/* -> develop -> QA -> UAT -> main -> Production

## Separation Rules
Each environment must have separate:
- database
- Redis
- storage bucket
- app key
- payment credentials
- mail configuration
- secrets

## Naming Convention
- uut-prf-dev
- uut-prf-qa
- uut-prf-uat
- uut-prf-prod