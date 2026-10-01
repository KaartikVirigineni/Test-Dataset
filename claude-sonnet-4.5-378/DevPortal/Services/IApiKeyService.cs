using DevPortal.Models;
using DevPortal.Models.DTOs;

namespace DevPortal.Services;

public interface IApiKeyService
{
    Task<ApiKey?> CreateApiKey(int projectId, CreateApiKeyRequest request, int userId);
    Task<bool> RevokeApiKey(int id, int userId);
    Task<List<ApiKey>> GetProjectApiKeys(int projectId, int userId);
}