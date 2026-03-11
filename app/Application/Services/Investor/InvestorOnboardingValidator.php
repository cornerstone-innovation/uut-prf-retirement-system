<?php

namespace App\Application\Services\Investor;

use Illuminate\Validation\ValidationException;

class InvestorOnboardingValidator
{
    public function validate(array $data): void
    {
        $this->validateInvestorTypeRules($data);
        $this->validateAddressRules($data['addresses'] ?? []);
        $this->validateNomineeRules($data['nominees'] ?? []);
    }

    private function validateInvestorTypeRules(array $data): void
    {
        $investorType = $data['investor_type'] ?? null;

        if ($investorType === 'corporate' && empty($data['company_name'])) {
            throw ValidationException::withMessages([
                'company_name' => ['Company name is required for corporate investors.'],
            ]);
        }

        if (in_array($investorType, ['individual', 'minor', 'joint'], true) && empty($data['full_name'])) {
            throw ValidationException::withMessages([
                'full_name' => ['Full name is required for non-corporate investors.'],
            ]);
        }
    }

    private function validateAddressRules(array $addresses): void
    {
        if (count($addresses) === 0) {
            throw ValidationException::withMessages([
                'addresses' => ['At least one address is required.'],
            ]);
        }

        $primaryCount = collect($addresses)->where('is_primary', true)->count();

        if ($primaryCount === 0) {
            throw ValidationException::withMessages([
                'addresses' => ['One primary address is required.'],
            ]);
        }

        if ($primaryCount > 1) {
            throw ValidationException::withMessages([
                'addresses' => ['Only one primary address is allowed.'],
            ]);
        }
    }

    private function validateNomineeRules(array $nominees): void
    {
        if (empty($nominees)) {
            return;
        }

        $totalAllocation = collect($nominees)
            ->sum(fn (array $nominee) => (float) ($nominee['allocation_percentage'] ?? 0));

        if (round($totalAllocation, 2) !== 100.00) {
            throw ValidationException::withMessages([
                'nominees' => ['Nominee allocation percentages must total 100.'],
            ]);
        }

        foreach ($nominees as $index => $nominee) {
            $isMinor = (bool) ($nominee['is_minor'] ?? false);

            if ($isMinor && empty($nominee['guardian_name'])) {
                throw ValidationException::withMessages([
                    "nominees.$index.guardian_name" => ['Guardian name is required for a minor nominee.'],
                ]);
            }

            if ($isMinor && empty($nominee['guardian_phone'])) {
                throw ValidationException::withMessages([
                    "nominees.$index.guardian_phone" => ['Guardian phone is required for a minor nominee.'],
                ]);
            }
        }
    }
}