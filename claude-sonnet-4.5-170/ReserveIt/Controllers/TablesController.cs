using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using ReserveIt.Data;
using ReserveIt.Models;

namespace ReserveIt.Controllers;

[ApiController]
[Route("api/[controller]")]
public class TablesController : ControllerBase
{
    private readonly AppDbContext _context;

    public TablesController(AppDbContext context)
    {
        _context = context;
    }

    [HttpGet]
    public async Task<ActionResult<List<Table>>> GetTables()
    {
        var tables = await _context.Tables.ToListAsync();
        return Ok(tables);
    }

    [HttpGet("{id}")]
    public async Task<ActionResult<Table>> GetTable(int id)
    {
        var table = await _context.Tables.FindAsync(id);
        if (table == null)
        {
            return NotFound();
        }

        return Ok(table);
    }

    [HttpPost]
    [Authorize(Policy = "AdminOnly")]
    public async Task<ActionResult<Table>> CreateTable([FromBody] Table table)
    {
        _context.Tables.Add(table);
        await _context.SaveChangesAsync();
        return CreatedAtAction(nameof(GetTable), new { id = table.Id }, table);
    }

    [HttpPut("{id}")]
    [Authorize(Policy = "AdminOnly")]
    public async Task<ActionResult<Table>> UpdateTable(int id, [FromBody] Table updatedTable)
    {
        var table = await _context.Tables.FindAsync(id);
        if (table == null)
        {
            return NotFound();
        }

        table.TableNumber = updatedTable.TableNumber;
        table.Capacity = updatedTable.Capacity;
        table.Location = updatedTable.Location;
        table.IsAvailable = updatedTable.IsAvailable;

        await _context.SaveChangesAsync();
        return Ok(table);
    }

    [HttpDelete("{id}")]
    [Authorize(Policy = "AdminOnly")]
    public async Task<ActionResult> DeleteTable(int id)
    {
        var table = await _context.Tables.FindAsync(id);
        if (table == null)
        {
            return NotFound();
        }

        _context.Tables.Remove(table);
        await _context.SaveChangesAsync();
        return NoContent();
    }
}