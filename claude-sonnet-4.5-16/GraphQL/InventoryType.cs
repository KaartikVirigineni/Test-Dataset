using WarehouseHub.Models;

namespace WarehouseHub.GraphQL;

public class InventoryType : ObjectType<Inventory>
{
    protected override void Configure(IObjectTypeDescriptor<Inventory> descriptor)
    {
        descriptor.Field(i => i.Id).Type<NonNullType<IdType>>();
        descriptor.Field(i => i.Quantity).Type<NonNullType<IntType>>();
        descriptor.Field(i => i.ReorderLevel).Type<NonNullType<IntType>>();
        descriptor.Field(i => i.Product).Type<ProductType>();
        descriptor.Field(i => i.Warehouse).Type<WarehouseType>();
    }
}