<?php

namespace App\Domain\Projects\Enums;

enum ProjectCorrectionField: string
{
    case Title = 'title';
    case ProjectArea = 'project_area_id';
    case Localization = 'localization';
    case MapData = 'map_data';
    case Goal = 'goal';
    case Description = 'description';
    case Argumentation = 'argumentation';
    case Availability = 'availability';
    case Category = 'category_id';
    case Recipients = 'recipients';
    case FreeOfCharge = 'free_of_charge';
    case Cost = 'cost';
    case SupportAttachment = 'support_attachment';
    case AgreementAttachment = 'agreement_attachment';
    case MapAttachment = 'map_attachment';
    case ParentAgreementAttachment = 'parent_agreement_attachment';
    case Attachments = 'attachments';

    /**
     * @return list<string>
     */
    public static function editableProjectColumns(): array
    {
        return [
            self::Title->value,
            self::ProjectArea->value,
            self::Localization->value,
            self::MapData->value,
            self::Goal->value,
            self::Description->value,
            self::Argumentation->value,
            self::Availability->value,
            self::Category->value,
            self::Recipients->value,
            self::FreeOfCharge->value,
        ];
    }
}
