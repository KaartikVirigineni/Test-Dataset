using GeoMapper.Models.DTOs;
using GeoMapper.Services;
using Microsoft.AspNetCore.Mvc;

namespace GeoMapper.Controllers;

[ApiController]
[Route("api/[controller]")]
public class AuthController : ControllerBase
{
    private readonly IAuthService _authService;

    public AuthController(IAuthService authService)
    {
        _authService = authService;
    }

    [HttpPost("register")]
    public async Task<IActionResult> Register([FromBody] RegisterRequest request)
    {
        if (string.IsNullOrWhiteSpace(request.Username) || string.IsNullOrWhiteSpace(request.Password))
        {
            return BadRequest(new { message = "Username and password are required" });
        }

        var user = await _authService.RegisterAsync(request.Username, request.Password);
        
        if (user == null)
        {
            return Conflict(new { message = "Username already exists" });
        }

        var token = _authService.GenerateJwtToken(user);
        return Ok(new AuthResponse { Token = token, Username = user.Username });
    }

    [HttpPost("login")]
    public async Task<IActionResult> Login([FromBody] LoginRequest request)
    {
        if (string.IsNullOrWhiteSpace(request.Username) || string.IsNullOrWhiteSpace(request.Password))
        {
            return BadRequest(new { message = "Username and password are required" });
        }

        var user = await _authService.LoginAsync(request.Username, request.Password);
        
        if (user == null)
        {
            return Unauthorized(new { message = "Invalid credentials" });
        }

        var token = _authService.GenerateJwtToken(user);
        return Ok(new AuthResponse { Token = token, Username = user.Username });
    }
}