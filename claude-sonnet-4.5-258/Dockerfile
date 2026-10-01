FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS base
WORKDIR /app
EXPOSE 80

FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src
COPY ["ExperimentHub.csproj", "./"]
RUN dotnet restore "ExperimentHub.csproj"
COPY . .
RUN dotnet build "ExperimentHub.csproj" -c Release -o /app/build

FROM build AS publish
RUN dotnet publish "ExperimentHub.csproj" -c Release -o /app/publish /p:UseAppHost=false

FROM base AS final
WORKDIR /app
COPY --from=publish /app/publish .
ENTRYPOINT ["dotnet", "ExperimentHub.dll"]