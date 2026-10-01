using ImageGallery.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace ImageGallery.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class ImagesController : ControllerBase
{
    private readonly IImageService _imageService;

    public ImagesController(IImageService imageService)
    {
        _imageService = imageService;
    }

    private int GetCurrentUserId()
    {
        var userIdClaim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        return int.TryParse(userIdClaim, out var userId) ? userId : 0;
    }

    [HttpPost("upload")]
    public async Task<IActionResult> Upload([FromForm] string title, [FromForm] string? description, [FromForm] IFormFile file)
    {
        var userId = GetCurrentUserId();
        
        if (userId == 0)
            return Unauthorized();

        if (string.IsNullOrWhiteSpace(title))
            return BadRequest(new { message = "Title is required" });

        var image = await _imageService.UploadImageAsync(userId, title, description, file);
        
        if (image == null)
            return BadRequest(new { message = "Failed to upload image. Ensure file is a valid image format." });

        return Ok(image);
    }

    [HttpGet]
    [AllowAnonymous]
    public async Task<IActionResult> GetAll()
    {
        var images = await _imageService.GetAllImagesAsync();
        return Ok(images);
    }

    [HttpGet("my")]
    public async Task<IActionResult> GetMy()
    {
        var userId = GetCurrentUserId();
        
        if (userId == 0)
            return Unauthorized();

        var images = await _imageService.GetUserImagesAsync(userId);
        return Ok(images);
    }

    [HttpGet("{id}")]
    [AllowAnonymous]
    public async Task<IActionResult> GetById(int id)
    {
        var image = await _imageService.GetImageByIdAsync(id);
        
        if (image == null)
            return NotFound();

        return Ok(image);
    }

    [HttpGet("{id}/file")]
    [AllowAnonymous]
    public async Task<IActionResult> GetFile(int id)
    {
        var result = await _imageService.GetImageFileAsync(id);
        
        if (result == null)
            return NotFound();

        return File(result.Value.content, result.Value.contentType, result.Value.fileName);
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var userId = GetCurrentUserId();
        
        if (userId == 0)
            return Unauthorized();

        var success = await _imageService.DeleteImageAsync(id, userId);
        
        if (!success)
            return NotFound(new { message = "Image not found or you don't have permission to delete it" });

        return NoContent();
    }
}