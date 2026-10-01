using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using WarehouseHub.Data;
using WarehouseHub.Models;

namespace WarehouseHub.Controllers;

[Authorize]
[ApiController]
[Route("api/[controller]")]
public class InventoryController : ControllerBase
{
    private readonly ApplicationDbContext _context;

    public InventoryController(ApplicationDbContext context)
    {
        _context = context;
    }

    [HttpGet]
    public async Task<ActionResult<IEnumerable<Inventory>>> GetInventories()
    {
        return await _context.Inventories
            .Include(i => i.Product)
            .Include(i => i.Warehouse)
            .ToListAsync();
    }

    [HttpGet("{id}")]
    public async Task<ActionResult<Inventory>> GetInventory(int id)
    {
        var inventory = await _context.Inventories
            .Include(i => i.Product)
            .Include(i => i.Warehouse)
            .FirstOrDefaultAsync(i => i.Id == id);
            
        if (inventory == null)
        {
            return NotFound();
        }
        return inventory;
    }

    [HttpGet("low-stock")]
    public async Task<ActionResult<IEnumerable<Inventory>>> GetLowStock()
    {
        return await _context.Inventories
            .Include(i => i.Product)
            .Include(i => i.Warehouse)
            .Where(i => i.Quantity <= i.ReorderLevel)
            .ToListAsync();
    }

    [HttpPost]
    public async Task<ActionResult<Inventory>> CreateInventory(CreateInventoryRequest request)
    {
        var existingInventory = await _context.Inventories
            .FirstOrDefaultAsync(i => i.ProductId == request.ProductId && i.WarehouseId == request.WarehouseId);

        if (existingInventory != null)
        {
            return Conflict(new { message = "Inventory for this product in this warehouse already exists" });
        }

        var inventory = new Inventory
        {
            ProductId = request.ProductId,
            WarehouseId = request.WarehouseId,
            Quantity = request.Quantity,
            ReorderLevel = request.ReorderLevel,
            LastRestocked = DateTime.UtcNow
        };

        _context.Inventories.Add(inventory);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetInventory), new { id = inventory.Id }, inventory);
    }

    [HttpPatch("{id}/quantity")]
    public async Task<IActionResult> UpdateQuantity(int id, UpdateQuantityRequest request)
    {
        var inventory = await _context.Inventories.FindAsync(id);
        if (inventory == null)
        {
            return NotFound();
        }

        inventory.Quantity = request.Quantity;
        inventory.LastRestocked = DateTime.UtcNow;

        await _context.SaveChangesAsync();
        return NoContent();
    }

    [HttpPatch("{id}/adjust")]
    public async Task<IActionResult> AdjustQuantity(int id, AdjustQuantityRequest request)
    {
        var inventory = await _context.Inventories.FindAsync(id);
        if (inventory == null)
        {
            return NotFound();
        }

        inventory.Quantity += request.Adjustment;
        if (inventory.Quantity < 0)
        {
            inventory.Quantity = 0;
        }
        inventory.LastRestocked = DateTime.UtcNow;

        await _context.SaveChangesAsync();
        return NoContent();
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteInventory(int id)
    {
        var inventory = await _context.Inventories.FindAsync(id);
        if (inventory == null)
        {
            return NotFound();
        }

        _context.Inventories.Remove(inventory);
        await _context.SaveChangesAsync();
        return NoContent();
    }
}

public record CreateInventoryRequest(int ProductId, int WarehouseId, int Quantity, int ReorderLevel);
public record UpdateQuantityRequest(int Quantity);
public record AdjustQuantityRequest(int Adjustment);