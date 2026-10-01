using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using WarehouseHub.Data;
using WarehouseHub.Models;

namespace WarehouseHub.Controllers;

[Authorize]
[ApiController]
[Route("api/[controller]")]
public class WarehousesController : ControllerBase
{
    private readonly ApplicationDbContext _context;

    public WarehousesController(ApplicationDbContext context)
    {
        _context = context;
    }

    [HttpGet]
    public async Task<ActionResult<IEnumerable<Warehouse>>> GetWarehouses()
    {
        return await _context.Warehouses.Include(w => w.Inventories).ToListAsync();
    }

    [HttpGet("{id}")]
    public async Task<ActionResult<Warehouse>> GetWarehouse(int id)
    {
        var warehouse = await _context.Warehouses.Include(w => w.Inventories).FirstOrDefaultAsync(w => w.Id == id);
        if (warehouse == null)
        {
            return NotFound();
        }
        return warehouse;
    }

    [HttpPost]
    public async Task<ActionResult<Warehouse>> CreateWarehouse(CreateWarehouseRequest request)
    {
        var warehouse = new Warehouse
        {
            Name = request.Name,
            Location = request.Location,
            Address = request.Address,
            Capacity = request.Capacity,
            IsActive = true,
            CreatedAt = DateTime.UtcNow
        };

        _context.Warehouses.Add(warehouse);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetWarehouse), new { id = warehouse.Id }, warehouse);
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateWarehouse(int id, UpdateWarehouseRequest request)
    {
        var warehouse = await _context.Warehouses.FindAsync(id);
        if (warehouse == null)
        {
            return NotFound();
        }

        if (request.Name != null) warehouse.Name = request.Name;
        if (request.Location != null) warehouse.Location = request.Location;
        if (request.Address != null) warehouse.Address = request.Address;
        if (request.Capacity.HasValue) warehouse.Capacity = request.Capacity.Value;
        if (request.IsActive.HasValue) warehouse.IsActive = request.IsActive.Value;

        await _context.SaveChangesAsync();
        return NoContent();
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteWarehouse(int id)
    {
        var warehouse = await _context.Warehouses.FindAsync(id);
        if (warehouse == null)
        {
            return NotFound();
        }

        _context.Warehouses.Remove(warehouse);
        await _context.SaveChangesAsync();
        return NoContent();
    }
}

public record CreateWarehouseRequest(string Name, string? Location, string? Address, int Capacity);
public record UpdateWarehouseRequest(string? Name, string? Location, string? Address, int? Capacity, bool? IsActive);