using Microsoft.AspNetCore.Mvc;
using DevPortal.Models.DTOs;
using DevPortal.Services;

namespace DevPortal.Controllers;

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
        var response = await _authService.Login(request);
        
        if (response == null)
        {
            return Unauthorized(new { message = "Invalid credentials" });
        }

        return Ok(response);
    }

    [HttpPost("register")]
    public async Task<IActionResult> Register([FromBody] RegisterRequest request)
    {
        var user = await _authService.Register(request);
        
        if (user == null)
        {
            return BadRequest(new { message = "User already exists" });
        }

        return Ok(new { message = "User registered successfully", email = user.Email });
    }
}