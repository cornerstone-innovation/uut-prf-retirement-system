<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Actions\Investor\CreateInvestorAction;
use App\Application\DTOs\Investor\CreateInvestorData;
use App\Application\DTOs\Investor\InvestorAddressData;
use App\Application\DTOs\Investor\InvestorNomineeData;
use App\Application\Services\Investor\InvestorOnboardingValidator;
use App\Http\Requests\Investor\StoreInvestorRequest;

class InvestorController extends Controller
{
    public function store(
        StoreInvestorRequest $request,
        InvestorOnboardingValidator $validator,
        CreateInvestorAction $action
    ): JsonResponse {
        $validated = $request->validated();

        $validator->validate($validated);

        $investor = $action->execute(
            new CreateInvestorData(
                investorType: $validated['investor_type'],
                fullName: $validated['full_name'],
                firstName: $validated['first_name'] ?? null,
                middleName: $validated['middle_name'] ?? null,
                lastName: $validated['last_name'] ?? null,
                companyName: $validated['company_name'] ?? null,
                dateOfBirth: $validated['date_of_birth'] ?? null,
                gender: $validated['gender'] ?? null,
                nationality: $validated['nationality'] ?? null,
                nationalIdNumber: $validated['national_id_number'] ?? null,
                taxIdentificationNumber: $validated['tax_identification_number'] ?? null,
                riskProfile: $validated['risk_profile'] ?? null,
                occupation: $validated['occupation'] ?? null,
                employerName: $validated['employer_name'] ?? null,
                sourceOfFunds: $validated['source_of_funds'] ?? null,
                notes: $validated['notes'] ?? null,
                email: $validated['email'] ?? null,
                phonePrimary: $validated['phone_primary'] ?? null,
                phoneSecondary: $validated['phone_secondary'] ?? null,
                alternateContactName: $validated['alternate_contact_name'] ?? null,
                alternateContactPhone: $validated['alternate_contact_phone'] ?? null,
                preferredContactMethod: $validated['preferred_contact_method'] ?? null,
                addresses: array_map(
                    fn (array $address) => new InvestorAddressData(
                        addressType: $address['address_type'],
                        country: $address['country'],
                        region: $address['region'] ?? null,
                        city: $address['city'] ?? null,
                        district: $address['district'] ?? null,
                        ward: $address['ward'] ?? null,
                        street: $address['street'] ?? null,
                        postalAddress: $address['postal_address'] ?? null,
                        postalCode: $address['postal_code'] ?? null,
                        isPrimary: (bool) $address['is_primary'],
                    ),
                    $validated['addresses']
                ),
                nominees: array_map(
                    fn (array $nominee) => new InvestorNomineeData(
                        fullName: $nominee['full_name'],
                        relationship: $nominee['relationship'],
                        dateOfBirth: $nominee['date_of_birth'] ?? null,
                        phone: $nominee['phone'] ?? null,
                        email: $nominee['email'] ?? null,
                        nationalIdNumber: $nominee['national_id_number'] ?? null,
                        allocationPercentage: (float) $nominee['allocation_percentage'],
                        isMinor: (bool) $nominee['is_minor'],
                        guardianName: $nominee['guardian_name'] ?? null,
                        guardianPhone: $nominee['guardian_phone'] ?? null,
                        address: $nominee['address'] ?? null,
                    ),
                    $validated['nominees'] ?? []
                ),
                createdBy: auth()->id(),
                updatedBy: auth()->id(),
            )
        );

        return response()->json([
            'message' => 'Investor created successfully.',
            'data' => $investor,
        ], 201);
    }
}