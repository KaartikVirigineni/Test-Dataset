using GeoMapper.Models.DTOs;
using GeoMapper.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace GeoMapper.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class LocationsController : ControllerBase
{
    private readonly IMapService _mapService;

    public LocationsController(IMapService mapService)
    {
        _mapService = mapService;
    }

    private int GetUserId()
    {
        var userIdClaim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        return int.Parse(userIdClaim ?? "0");
    }

    [HttpGet]
    [AllowAnonymous]
    public async Task<IActionResult> GetAllLocations()
    {
        var locations = await _mapService.GetAllLocationsAsync();
        return Ok(locations);
    }

    [HttpGet("my")]
    public async Task<IActionResult> GetMyLocations()
    {
        var userId = GetUserId();
        var locations = await _mapService.GetUserLocationsAsync(userId);
        return Ok(locations);
    }

    [HttpGet("{id}")]
    [AllowAnonymous]
    public async Task<IActionResult> GetLocation(int id)
    {
        var location = await _mapService.GetLocationByIdAsync(id);
        
        if (location == null)
        {
            return NotFound(new { message = "Location not found" });
        }

        return Ok(location);
    }

    [HttpPost]
    public async Task<IActionResult> CreateLocation([FromBody] MapLocationRequest request)
    {
        var userId = GetUserId();
        var location = await _mapService.CreateLocationAsync(userId, request);
        
        if (location == null)
        {
            return BadRequest(new { message = "Failed to create location" });
        }

        var response = await _mapService.GetLocationByIdAsync(location.Id);
        return CreatedAtAction(nameof(GetLocation), new { id = location.Id }, response);
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateLocation(int id, [FromBody] MapLocationRequest request)
    {
        var userId = GetUserId();
        var location = await _mapService.UpdateLocationAsync(id, userId, request);
        
        if (location == null)
        {
            return NotFound(new { message = "Location not found or unauthorized" });
        }

        var response = await _mapService.GetLocationByIdAsync(location.Id);
        return Ok(response);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteLocation(int id)
    {
        var userId = GetUserId();
        var success = await _mapService.DeleteLocationAsync(id, userId);
        
        if (!success)
        {
            return NotFound(new { message = "Location not found or unauthorized" });
        }

        return NoContent();
    }
}