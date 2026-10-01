using GeoMapper.Models;
using GeoMapper.Models.DTOs;

namespace GeoMapper.Services;

public interface IMapService
{
    Task<MapLocation?> CreateLocationAsync(int userId, MapLocationRequest request);
    Task<List<MapLocationResponse>> GetAllLocationsAsync();
    Task<List<MapLocationResponse>> GetUserLocationsAsync(int userId);
    Task<MapLocationResponse?> GetLocationByIdAsync(int id);
    Task<MapLocation?> UpdateLocationAsync(int id, int userId, MapLocationRequest request);
    Task<bool> DeleteLocationAsync(int id, int userId);
}