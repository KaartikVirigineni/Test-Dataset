using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using RealtimeChat.Models;
using RealtimeChat.Services;
using System.Security.Claims;

namespace RealtimeChat.Controllers;

[ApiController]
[Route("api/rooms/{roomId}/[controller]")]
[Authorize(Policy = "UserOrAdmin")]
public class MessagesController : ControllerBase
{
    private readonly IChatService _chatService;

    public MessagesController(IChatService chatService)
    {
        _chatService = chatService;
    }

    [HttpGet]
    public async Task<IActionResult> GetMessages(int roomId)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var messages = await _chatService.GetMessagesAsync(roomId, userId);
        return Ok(messages);
    }

    [HttpPost]
    public async Task<IActionResult> SendMessage(int roomId, [FromBody] SendMessageRequest request)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var message = await _chatService.SendMessageAsync(roomId, userId, request);
        
        if (message == null)
        {
            return BadRequest(new { message = "Failed to send message" });
        }

        return CreatedAtAction(nameof(GetMessages), new { roomId }, message);
    }
}