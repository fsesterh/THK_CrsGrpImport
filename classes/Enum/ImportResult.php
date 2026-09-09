<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\CrsGrpImport\Enum;

enum ImportResult: string
{
    case NONE = '';

    case IGNORE = 'Entry has set ignore action, ignoring entry.';
    case NO_VALID_ACTION = 'No valid action found, ignoring entry.';
    case CREATED_SUCCESSFULLY = 'Object created successfully.';
    case UPDATED_SUCCESSFULLY = 'Object updated successfully.';
    case DATASET_IGNORED = 'Dataset ignored.';
    case REF_ID_NOT_FOUND = 'RefId not found. Data not processed.';
    case NO_REF_ID_GIVEN_FOR_UPDATE = 'No RefId specified for update. Data not processed.';
    case REF_ID_AND_TYPE_DO_NOT_MATCH = 'RefId does not match object-type. Data not processed.';
    case NO_PASSWORD_GIVEN_FOR_TYPE_TWO = 'No registration password specified. Data not processed.';
    case UNUSABLE_ADMIN_FOUND = 'One or all of the user accounts for admins not found. Data not processed.';
    case NO_COURSE_IN_COURSE = 'Creation of course in course not possible.';
    case UNKNOWN_OBJECT_TYPE = 'Object type is not known, Data not processed.';
    case DATASET_INCOMPLETE = 'Dataset incomplete. Data not processed.';
    case DATASET_INVALID = 'Dataset invalid. Data not processed.';
    case UPDATE_OBJECT_NOT_IN_SUBTREE = 'Dataset invalid. Object for update is not in sub tree.';
    case UPDATE_OBJECT_HAS_DIFFERENT_TYPE = 'Dataset invalid. Object for update has not the right object type.';
    case OBJECT_IN_TRASH_IGNORE = 'Object is in trash, ignoring.';
    case AVAILABILITY = 'Setting Availability not successful.';
    case UNSUPPORTED_ACTION = 'Dataset invalid, the provided action is not supported';
    case UNSUPPORTED_OBJECT_TYPE = 'Dataset invalid, the provided object type is not supported';
    case UNSUPPORTED_REGISTRATION_TYPE = 'Dataset invalid, the provided registration type is not supported';
    case DIDACTIC_TEMPLATE_ID_1_NOT_ALLOWED = 'Dataset invalid, the didactic template ID 1 is not allowed';
    case DIDACTIC_TEMPLATE_ID_NOT_SUPPORTED_OR_NOT_ENABLED = 'Dataset invalid, the provided didactic template ID is not supported by the object type or the template is disabled';
    case NO_ADMINS_PROVIDED = 'Dataset invalid, no admins provided';
    case NO_ADMIN_USERS_COULD_BE_DETERMINED = 'Dataset invalid, no admin user accounts could be determined from the provided string';
    case MISSING_GERMAN_TITLE = 'Dataset invalid, no German object title provided';
    case MIN_MEMBERS_GREATER_THAN_MAX_MEMBERS = 'Dataset invalid, the amount of min members is greater than the amount of max members';
    case NO_UPDATE_ACTION_ALLOWED_FOR_LINKS = 'Dataset invalid, container reference object do not support an update action';
    case MISSING_REF_ID_FOR_LINK = 'Dataset invalid, a ref_id has to be provided for course or group references/links';
    case INVALID_REF_ID_FOR_LINK = 'Dataset invalid, the provided ref_id for the reference/link could not be found';
    case TYPE_MISMATCH_FOR_LINK = 'Dataset invalid, the object type of the provided ref_id for the reference/link does not match the object type of the container';

}
