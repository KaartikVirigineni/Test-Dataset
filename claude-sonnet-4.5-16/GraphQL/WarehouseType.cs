using WarehouseHub.Models;

namespace WarehouseHub.GraphQL;

public class WarehouseType : ObjectType<Warehouse>
{
    protected override void Configure(IObjectTypeDescriptor<Warehouse> descriptor)
    {
        descriptor.Field(w => w.Id).Type<NonNullType<IdType>>();
        descriptor.Field(w => w.Name).Type<NonNullType<StringType>>();
        descriptor.Field(w => w.Capacity).Type<NonNullType<IntType>>();
        descriptor.Field(w => w.IsActive).Type<NonNullType<BooleanType>>();
        descriptor.Field(w => w.Inventories).Type<ListType<InventoryType>>();
    }
}