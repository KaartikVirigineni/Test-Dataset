using Microsoft.AspNetCore.Mvc;

namespace ImageGallery.Controllers;

[ApiController]
public class SwaggerController : ControllerBase
{
    private readonly IWebHostEnvironment _environment;

    public SwaggerController(IWebHostEnvironment environment)
    {
        _environment = environment;
    }

    [HttpGet("/swagger")]
    public IActionResult GetSwaggerSpec()
    {
        var filePath = Path.Combine(_environment.ContentRootPath, "openapi.yaml");
        
        if (!System.IO.File.Exists(filePath))
            return NotFound();

        var content = System.IO.File.ReadAllText(filePath);
        return Content(content, "application/yaml");
    }
}