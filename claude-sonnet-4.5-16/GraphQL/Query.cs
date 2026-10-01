using HotChocolate.Authorization;
using Microsoft.EntityFrameworkCore;
using WarehouseHub.Data;
using WarehouseHub.Models;

namespace WarehouseHub.GraphQL;

public class Query
{
    [Authorize]
    public async Task<List<Product>> GetProducts([Service] ApplicationDbContext context)
    {
        return await context.Products.Include(p => p.Inventories).ToListAsync();
    }

    [Authorize]
    public async Task<Product?> GetProduct(int id, [Service] ApplicationDbContext context)
    {
        return await context.Products.Include(p => p.Inventories).FirstOrDefaultAsync(p => p.Id == id);
    }

    [Authorize]
    public async Task<List<Warehouse>> GetWarehouses([Service] ApplicationDbContext context)
    {
        return await context.Warehouses.Include(w => w.Inventories).ToListAsync();
    }

    [Authorize]
    public async Task<Warehouse?> GetWarehouse(int id, [Service] ApplicationDbContext context)
    {
        return await context.Warehouses.Include(w => w.Inventories).FirstOrDefaultAsync(w => w.Id == id);
    }

    [Authorize]
    public async Task<List<Inventory>> GetInventories([Service] ApplicationDbContext context)
    {
        return await context.Inventories
            .Include(i => i.Product)
            .Include(i => i.Warehouse)
            .ToListAsync();
    }

    [Authorize]
    public async Task<Inventory?> GetInventory(int id, [Service] ApplicationDbContext context)
    {
        return await context.Inventories
            .Include(i => i.Product)
            .Include(i => i.Warehouse)
            .FirstOrDefaultAsync(i => i.Id == id);
    }

    [Authorize]
    public async Task<List<Inventory>> GetLowStockItems([Service] ApplicationDbContext context)
    {
        return await context.Inventories
            .Include(i => i.Product)
            .Include(i => i.Warehouse)
            .Where(i => i.Quantity <= i.ReorderLevel)
            .ToListAsync();
    }
}