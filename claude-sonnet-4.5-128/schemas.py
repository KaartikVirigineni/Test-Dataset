from marshmallow import Schema, fields, validate

class UserRegistrationSchema(Schema):
    username = fields.Str(required=True, validate=validate.Length(min=3, max=50))
    email = fields.Email(required=True)
    password = fields.Str(required=True, validate=validate.Length(min=6))

class UserLoginSchema(Schema):
    username = fields.Str(required=True)
    password = fields.Str(required=True)

class GistCreateSchema(Schema):
    title = fields.Str(required=True, validate=validate.Length(min=1, max=200))
    description = fields.Str(allow_none=True)
    content = fields.Str(required=True)
    language = fields.Str(missing='text')
    is_public = fields.Bool(missing=True)

class GistUpdateSchema(Schema):
    title = fields.Str(validate=validate.Length(min=1, max=200))
    description = fields.Str(allow_none=True)
    content = fields.Str()
    language = fields.Str()
    is_public = fields.Bool()

class GistResponseSchema(Schema):
    id = fields.Int()
    title = fields.Str()
    description = fields.Str()
    content = fields.Str()
    language = fields.Str()
    is_public = fields.Bool()
    owner_id = fields.Int()
    owner_username = fields.Str()
    created_at = fields.DateTime()
    updated_at = fields.DateTime()

class UserResponseSchema(Schema):
    id = fields.Int()
    username = fields.Str()
    email = fields.Email()
    role = fields.Str()
    created_at = fields.DateTime()