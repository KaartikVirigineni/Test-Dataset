using Microsoft.AspNetCore.Mvc;
using WarehouseHub.Services;

namespace WarehouseHub.Controllers;

[ApiController]
[Route("api/[controller]")]
public class AuthController : ControllerBase
{
    private readonly IAuthService _authService;

    public AuthController(IAuthService authService)
    {
        _authService = authService;
    }

    [HttpPost("login")]
    public async Task<IActionResult> Login([FromBody] LoginRequest request)
    {
        var token = await _authService.AuthenticateAsync(request.Email, request.Password);
        if (token == null)
        {
            return Unauthorized(new { message = "Invalid credentials" });
        }

        return Ok(new { token });
    }

    [HttpPost("register")]
    public async Task<IActionResult> Register([FromBody] RegisterRequest request)
    {
        var success = await _authService.RegisterAsync(
            request.Email,
            request.Password,
            request.FirstName,
            request.LastName);

        if (!success)
        {
            return BadRequest(new { message = "Registration failed" });
        }

        var token = await _authService.AuthenticateAsync(request.Email, request.Password);
        return Ok(new { token });
    }
}

public record LoginRequest(string Email, string Password);
public record RegisterRequest(string Email, string Password, string? FirstName, string? LastName);