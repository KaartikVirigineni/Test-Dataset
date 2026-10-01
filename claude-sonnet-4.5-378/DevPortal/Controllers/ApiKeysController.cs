using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;
using DevPortal.Models.DTOs;
using DevPortal.Services;

namespace DevPortal.Controllers;

[ApiController]
[Route("api/projects/{projectId}/[controller]")]
[Authorize(Policy = "Developer")]
public class ApiKeysController : ControllerBase
{
    private readonly IApiKeyService _apiKeyService;

    public ApiKeysController(IApiKeyService apiKeyService)
    {
        _apiKeyService = apiKeyService;
    }

    private int GetUserId()
    {
        var userIdClaim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        return int.Parse(userIdClaim ?? "0");
    }

    [HttpPost]
    public async Task<IActionResult> CreateApiKey(int projectId, [FromBody] CreateApiKeyRequest request)
    {
        var userId = GetUserId();
        var apiKey = await _apiKeyService.CreateApiKey(projectId, request, userId);
        
        if (apiKey == null)
        {
            return BadRequest(new { message = "Failed to create API key or project not found" });
        }

        return CreatedAtAction(nameof(GetApiKeys), new { projectId }, apiKey);
    }

    [HttpGet]
    public async Task<IActionResult> GetApiKeys(int projectId)
    {
        var userId = GetUserId();
        var apiKeys = await _apiKeyService.GetProjectApiKeys(projectId, userId);
        return Ok(apiKeys);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> RevokeApiKey(int projectId, int id)
    {
        var userId = GetUserId();
        var result = await _apiKeyService.RevokeApiKey(id, userId);
        
        if (!result)
        {
            return NotFound(new { message = "API key not found" });
        }

        return NoContent();
    }
}