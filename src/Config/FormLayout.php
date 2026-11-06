<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config;

use KawayanMedia\LeaveForms\Config\Layout\CtoApplicationLayout;
use KawayanMedia\LeaveForms\Config\Layout\LeaveApplicationLayout;
use KawayanMedia\LeaveForms\Config\Layout\LeaveCertificationLayout;
use KawayanMedia\LeaveForms\Config\Layout\MoneyValueLayout;
use KawayanMedia\LeaveForms\Config\Layout\RetirementScheduleLayout;
use KawayanMedia\LeaveForms\Config\Layout\ServiceRecordLayout;
use KawayanMedia\LeaveForms\Config\Layout\StatementOfLeaveLayout;

/**
 * The templates and the per-document layout.
 *
 * Templates are drawn at their natural size, exactly like the legacy
 * controllers called useTemplate($id) without a width, so every coordinate in
 * the layout classes is a template millimetre.
 */
final class FormLayout
{
    public const SERVICE_RECORD = 'service-record';

    public const LEAVE_CERTIFICATION = 'leave-certification';

    public const LEAVE_APPLICATION = 'leave-application';

    public const CTO_APPLICATION = 'cto-application';

    public const MONEY_VALUE = 'money-value';

    public const RETIREMENT_SCHEDULE = 'retirement-schedule';

    public const STATEMENT_OF_LEAVE = 'statement-of-leave';

    /**
     * @param array<string, string> $templates document key => absolute template path
     */
    public function __construct(
        public readonly array $templates = [],
        public readonly bool $compress = true,
        public readonly ServiceRecordLayout $serviceRecord = new ServiceRecordLayout(),
        public readonly LeaveCertificationLayout $leaveCertification = new LeaveCertificationLayout(),
        public readonly LeaveApplicationLayout $leaveApplication = new LeaveApplicationLayout(),
        public readonly CtoApplicationLayout $ctoApplication = new CtoApplicationLayout(),
        public readonly MoneyValueLayout $moneyValue = new MoneyValueLayout(),
        public readonly RetirementScheduleLayout $retirementSchedule = new RetirementScheduleLayout(),
        public readonly StatementOfLeaveLayout $statementOfLeave = new StatementOfLeaveLayout(),
    ) {
    }

    /**
     * @param array<string, string>|null $templates
     */
    public static function legacyIhrmis(?array $templates = null, bool $compress = true): self
    {
        return new self(
            templates: $templates ?? self::bundledTemplates(),
            compress: $compress,
        );
    }

    public static function fromDirectory(string $directory, bool $compress = true): self
    {
        return self::legacyIhrmis(self::templatesIn($directory), $compress);
    }

    /**
     * @return array<string, string>
     */
    public static function bundledTemplates(): array
    {
        return self::templatesIn(dirname(__DIR__, 2) . '/resources/templates');
    }

    /**
     * @return array<string, string>
     */
    public static function templatesIn(string $directory): array
    {
        $directory = rtrim($directory, '/\\');

        return [
            self::LEAVE_CERTIFICATION => $directory . '/certification4.pdf',
            self::LEAVE_APPLICATION => $directory . '/application_for_leave.pdf',
            self::CTO_APPLICATION => $directory . '/app_for_offset.pdf',
            self::MONEY_VALUE => $directory . '/computation_money_value.pdf',
            self::RETIREMENT_SCHEDULE => $directory . '/schedule_retirement_pay.pdf',
            self::STATEMENT_OF_LEAVE => $directory . '/statement_leave.pdf',
        ];
    }

    /**
     * @param array<string, string> $templates
     */
    public function withTemplates(array $templates): self
    {
        return new self(
            templates: $templates,
            compress: $this->compress,
            serviceRecord: $this->serviceRecord,
            leaveCertification: $this->leaveCertification,
            leaveApplication: $this->leaveApplication,
            ctoApplication: $this->ctoApplication,
            moneyValue: $this->moneyValue,
            retirementSchedule: $this->retirementSchedule,
            statementOfLeave: $this->statementOfLeave,
        );
    }

    public function template(string $documentKey): string
    {
        if ($documentKey === self::SERVICE_RECORD) {
            return $this->serviceRecord->template;
        }

        return $this->templates[$documentKey] ?? '';
    }

    public function hasTemplate(string $documentKey): bool
    {
        $template = $this->template($documentKey);

        return $template !== '' && is_file($template);
    }

    /**
     * Page geometry of one document.
     *
     * @return array{orientation: string, unit: string, pageSize: string, margin: float}
     */
    public function geometry(string $documentKey): array
    {
        [$orientation, $unit, $pageSize] = match ($documentKey) {
            self::SERVICE_RECORD => [$this->serviceRecord->orientation, $this->serviceRecord->unit, $this->serviceRecord->pageSize],
            self::LEAVE_CERTIFICATION => [$this->leaveCertification->orientation, $this->leaveCertification->unit, $this->leaveCertification->pageSize],
            self::LEAVE_APPLICATION => [$this->leaveApplication->orientation, $this->leaveApplication->unit, $this->leaveApplication->pageSize],
            self::CTO_APPLICATION => [$this->ctoApplication->orientation, $this->ctoApplication->unit, $this->ctoApplication->pageSize],
            self::MONEY_VALUE => [$this->moneyValue->orientation, $this->moneyValue->unit, $this->moneyValue->pageSize],
            self::RETIREMENT_SCHEDULE => [$this->retirementSchedule->orientation, $this->retirementSchedule->unit, $this->retirementSchedule->pageSize],
            default => [$this->statementOfLeave->orientation, $this->statementOfLeave->unit, $this->statementOfLeave->pageSize],
        };

        return [
            'orientation' => $orientation,
            'unit' => $unit,
            'pageSize' => $pageSize,
            'margin' => $documentKey === self::SERVICE_RECORD ? $this->serviceRecord->leftMargin : 10.0,
        ];
    }
}
