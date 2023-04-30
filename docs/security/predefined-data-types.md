# Predefined Data Types

!> **Important: Internally used data types and property names begin with an underscore `_`.**  
Deviating from this practice may lead to confusion and potential issues for developers who are working with the API.
Therefore, it is recommended to adhere to this guideline for the sake of maintainability and usability.

## Node Types

### `User`

Required properties:

- `username`: Field is globally unique, i.e. requests updating this field can fail if the new username is already taken.
- `password`: In POST, PUT & PATCH endpoints only, used to update the user's password hash.
- `_passwordHash`: Internal property, not accessible via the API.

### `Group`

Required properties:

- `name`

### `Session`

Required properties:

- `name`
- `token`: Only returned in the initial POST request.
- `_tokenHash`: Internal property, not accessible via the API.
- `validUntil`: Can only be set in the initial POST request.

### `Workflow`

See also [Workflow](/security/workflow).

### `State`

Single state of a workflow.  
See also [Workflow](/security/workflow).

### `StateTransition`

See also [Workflow](/security/workflow).

## Relation Types

### `OWNS`

No properties required.  
See also [Ownership](/security/ownership).

### `HAS_READ_ACCESS`

See also [Access](/security/access).

Optional properties:

- `onLabel`
- `onParentLabel`
- `withState`
- `onCreatedByUser`

### `HAS_SEARCH_ACCESS`

See also [Access](/security/access).

Optional properties:

- `onLabel`
- `onParentLabel`
- `withState`
- `onCreatedByUser`

### `HAS_CREATE_ACCESS`

See also [Access](/security/access).

Optional properties:

- `onLabel`
- `onParentLabel`
- `withState`
- `onCreatedByUser`

### `HAS_UPDATE_ACCESS`

See also [Access](/security/access).

Optional properties:

- `onLabel`
- `onParentLabel`
- `withState`
- `onCreatedByUser`

### `HAS_DELETE_ACCESS`

See also [Access](/security/access).

Optional properties:

- `onLabel`
- `onParentLabel`
- `withState`
- `onCreatedByUser`

### `HAS_STATE`

### `CREATED`

Used to indicate that a specific user created the element.

## Internal Properties

Internal properties can be set on most data elements by the API. They are intended to perform optimization or
automation, and can not be directly changed by the users.

### `_hasFileState`

This property can have multiple states:

- `null` (property is missing): Data element does not have a file associated with it.
- `uploaded`: Data element has a successfully uploaded file associated with it.
- `uploading`: Data element is currently associated with a file upload.

### `_fileMetadata`

Contains different metadata for the file associated with the data element.  
Text extracted from the uploaded file will be stored in the metadata as well.

### `_created`

The creation date of a data element

### `_updated`

Date of the last time the element was changed.
