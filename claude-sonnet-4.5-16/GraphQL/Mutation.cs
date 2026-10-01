using HotChocolate.Authorization;
using Microsoft.EntityFrameworkCore;
using WarehouseHub.Data;
using WarehouseHub.Models;
using WarehouseHub.Services;

namespace WarehouseHub.GraphQL;

public class Mutation
{
    public async Task<AuthPayload> Login(string email, string password, [Service] IAuthService authService)
    {
        var token = await authService.AuthenticateAsync(email, password);
        if (token == null)
        {
            throw new GraphQLException("Invalid credentials");
        }

        return new AuthPayload { Token = token };
    }

    public async Task<AuthPayload> Register(
        string email,
        string password,
        string? firstName,
        string? lastName,
        [Service] IAuthService authService)
    {
        var success = await authService.RegisterAsync(email, password, firstName, lastName);
        if (!success)
        {
            throw new GraphQLException("Registration failed");
        }

        var token = await authService.AuthenticateAsync(email, password);
        return new AuthPayload { Token = token! };
    }

    [Authorize]
    public async Task<Product> CreateProduct(
        string name,
        string sku,
        decimal price,
        string? description,
        [Service] ApplicationDbContext context)
    {
        var product = new Product
        {
            Name = name,
            SKU = sku,
            Price = price,
            Description = description,
            CreatedAt = DateTime.UtcNow
        };

        context.Products.Add(product);
        await context.SaveChangesAsync();
        return product;
    }

    [Authorize]
    public async Task<Product> UpdateProduct(
        int id,
        string? name,
        decimal? price,
        string? description,
        [Service] ApplicationDbContext context)
    {
        var product = await context.Products.FindAsync(id);
        if (product == null)
        {
            throw new GraphQLException("Product not found");
        }

        if (name != null) product.Name = name;
        if (price.HasValue) product.Price = price.Value;
        if (description != null) product.Description = description;
        product.UpdatedAt = DateTime.UtcNow;

        await context.SaveChangesAsync();
        return product;
    }

    [Authorize]
    public async Task<bool> DeleteProduct(int id, [Service] ApplicationDbContext context)
    {
        var product = await context.Products.FindAsync(id);
        if (product == null)
        {
            return false;
        }

        context.Products.Remove(product);
        await context.SaveChangesAsync();
        return true;
    }

    [Authorize]
    public async Task<Warehouse> CreateWarehouse(
        string name,
        string? location,
        string? address,
        int capacity,
        [Service] ApplicationDbContext context)
    {
        var warehouse = new Warehouse
        {
            Name = name,
            Location = location,
            Address = address,
            Capacity = capacity,
            IsActive = true,
            CreatedAt = DateTime.UtcNow
        };

        context.Warehouses.Add(warehouse);
        await context.SaveChangesAsync();
        return warehouse;
    }

    [Authorize]
    public async Task<Warehouse> UpdateWarehouse(
        int id,
        string? name,
        string? location,
        string? address,
        int? capacity,
        bool? isActive,
        [Service] ApplicationDbContext context)
    {
        var warehouse = await context.Warehouses.FindAsync(id);
        if (warehouse == null)
        {
            throw new GraphQLException("Warehouse not found");
        }

        if (name != null) warehouse.Name = name;
        if (location != null) warehouse.Location = location;
        if (address != null) warehouse.Address = address;
        if (capacity.HasValue) warehouse.Capacity = capacity.Value;
        if (isActive.HasValue) warehouse.IsActive = isActive.Value;

        await context.SaveChangesAsync();
        return warehouse;
    }

    [Authorize]
    public async Task<Inventory> CreateInventory(
        int productId,
        int warehouseId,
        int quantity,
        int reorderLevel,
        [Service] ApplicationDbContext context)
    {
        var existingInventory = await context.Inventories
            .FirstOrDefaultAsync(i => i.ProductId == productId && i.WarehouseId == warehouseId);

        if (existingInventory != null)
        {
            throw new GraphQLException("Inventory for this product in this warehouse already exists");
        }

        var inventory = new Inventory
        {
            ProductId = productId,
            WarehouseId = warehouseId,
            Quantity = quantity,
            ReorderLevel = reorderLevel,
            LastRestocked = DateTime.UtcNow
        };

        context.Inventories.Add(inventory);
        await context.SaveChangesAsync();
        return inventory;
    }

    [Authorize]
    public async Task<Inventory> UpdateInventoryQuantity(
        int id,
        int quantity,
        [Service] ApplicationDbContext context)
    {
        var inventory = await context.Inventories.FindAsync(id);
        if (inventory == null)
        {
            throw new GraphQLException("Inventory not found");
        }

        inventory.Quantity = quantity;
        inventory.LastRestocked = DateTime.UtcNow;

        await context.SaveChangesAsync();
        return inventory;
    }

    [Authorize]
    public async Task<Inventory> AdjustInventory(
        int id,
        int adjustment,
        [Service] ApplicationDbContext context)
    {
        var inventory = await context.Inventories.FindAsync(id);
        if (inventory == null)
        {
            throw new GraphQLException("Inventory not found");
        }

        inventory.Quantity += adjustment;
        if (inventory.Quantity < 0)
        {
            inventory.Quantity = 0;
        }
        inventory.LastRestocked = DateTime.UtcNow;

        await context.SaveChangesAsync();
        return inventory;
    }
}

public class AuthPayload
{
    public string Token { get; set; } = string.Empty;
}