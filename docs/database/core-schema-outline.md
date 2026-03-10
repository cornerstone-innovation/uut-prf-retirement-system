# Identity, Investor and KYC Schema Design

## Users
- id
- uuid
- name
- email
- phone
- password
- is_active
- last_login_at
- investor_id nullable
- timestamps

## Investors
- id
- uuid
- investor_number
- investor_type
- first_name nullable
- middle_name nullable
- last_name nullable
- full_name
- company_name nullable
- date_of_birth nullable
- gender nullable
- nationality nullable
- national_id_number nullable
- tax_identification_number nullable
- onboarding_status
- kyc_status
- investor_status
- risk_profile nullable
- occupation nullable
- employer_name nullable
- source_of_funds nullable
- notes nullable
- created_by nullable
- updated_by nullable
- timestamps

## Investor Contacts
- id
- investor_id
- email nullable
- phone_primary nullable
- phone_secondary nullable
- alternate_contact_name nullable
- alternate_contact_phone nullable
- preferred_contact_method nullable
- timestamps

## Investor Addresses
- id
- investor_id
- address_type
- country
- region nullable
- city nullable
- district nullable
- ward nullable
- street nullable
- postal_address nullable
- postal_code nullable
- is_primary
- timestamps

## Investor Nominees
- id
- investor_id
- full_name
- relationship
- date_of_birth nullable
- phone nullable
- email nullable
- national_id_number nullable
- allocation_percentage
- is_minor
- guardian_name nullable
- guardian_phone nullable
- address nullable
- timestamps

## Investor KYC Profiles
- id
- investor_id
- kyc_reference
- document_status
- identity_verification_status
- address_verification_status
- tax_verification_status
- pep_check_status nullable
- sanctions_check_status nullable
- aml_risk_level nullable
- review_notes nullable
- submitted_at nullable
- reviewed_at nullable
- reviewed_by nullable
- approved_at nullable
- rejected_at nullable
- expires_at nullable
- timestamps