using GeoMapper.Data;
using GeoMapper.Models;
using GeoMapper.Models.DTOs;
using Microsoft.EntityFrameworkCore;

namespace GeoMapper.Services;

public class MapService : IMapService
{
    private readonly AppDbContext _context;

    public MapService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<MapLocation?> CreateLocationAsync(int userId, MapLocationRequest request)
    {
        var location = new MapLocation
        {
            Name = request.Name,
            Description = request.Description,
            Latitude = request.Latitude,
            Longitude = request.Longitude,
            Category = request.Category,
            UserId = userId,
            CreatedAt = DateTime.UtcNow
        };

        _context.MapLocations.Add(location);
        await _context.SaveChangesAsync();

        return location;
    }

    public async Task<List<MapLocationResponse>> GetAllLocationsAsync()
    {
        return await _context.MapLocations
            .Include(m => m.User)
            .Select(m => new MapLocationResponse
            {
                Id = m.Id,
                Name = m.Name,
                Description = m.Description,
                Latitude = m.Latitude,
                Longitude = m.Longitude,
                Category = m.Category,
                CreatedAt = m.CreatedAt,
                Username = m.User.Username
            })
            .ToListAsync();
    }

    public async Task<List<MapLocationResponse>> GetUserLocationsAsync(int userId)
    {
        return await _context.MapLocations
            .Include(m => m.User)
            .Where(m => m.UserId == userId)
            .Select(m => new MapLocationResponse
            {
                Id = m.Id,
                Name = m.Name,
                Description = m.Description,
                Latitude = m.Latitude,
                Longitude = m.Longitude,
                Category = m.Category,
                CreatedAt = m.CreatedAt,
                Username = m.User.Username
            })
            .ToListAsync();
    }

    public async Task<MapLocationResponse?> GetLocationByIdAsync(int id)
    {
        return await _context.MapLocations
            .Include(m => m.User)
            .Where(m => m.Id == id)
            .Select(m => new MapLocationResponse
            {
                Id = m.Id,
                Name = m.Name,
                Description = m.Description,
                Latitude = m.Latitude,
                Longitude = m.Longitude,
                Category = m.Category,
                CreatedAt = m.CreatedAt,
                Username = m.User.Username
            })
            .FirstOrDefaultAsync();
    }

    public async Task<MapLocation?> UpdateLocationAsync(int id, int userId, MapLocationRequest request)
    {
        var location = await _context.MapLocations.FindAsync(id);
        
        if (location == null || location.UserId != userId)
        {
            return null;
        }

        location.Name = request.Name;
        location.Description = request.Description;
        location.Latitude = request.Latitude;
        location.Longitude = request.Longitude;
        location.Category = request.Category;

        await _context.SaveChangesAsync();
        return location;
    }

    public async Task<bool> DeleteLocationAsync(int id, int userId)
    {
        var location = await _context.MapLocations.FindAsync(id);
        
        if (location == null || location.UserId != userId)
        {
            return false;
        }

        _context.MapLocations.Remove(location);
        await _context.SaveChangesAsync();
        return true;
    }
}